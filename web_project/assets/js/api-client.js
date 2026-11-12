/**
 * Cliente de comunicación con api/auth.php y gestión de sesión
 */
window.ApiClient = {
    key: null,

    init() {
        if (this.key) return;
        if (window.CryptoJS && window.APP_KEY) {
            this.key = CryptoJS.SHA256(window.APP_KEY);
        }
    },

    getToken() {
        return localStorage.getItem('si_auth_token') || '';
    },

    getUsername() {
        return localStorage.getItem('si_auth_user') || '';
    },

    setSession(token, username) {
        localStorage.setItem('si_auth_token', token);
        localStorage.setItem('si_auth_user', username);
    },

    clearSession() {
        localStorage.removeItem('si_auth_token');
        localStorage.removeItem('si_auth_user');
    },

    encode(dataObj) {
        this.init();
        if (!this.key || !window.CryptoJS) return dataObj;
        try {
            const plain = JSON.stringify(dataObj);
            const iv = CryptoJS.lib.WordArray.random(16);
            const enc = CryptoJS.AES.encrypt(plain, this.key, {
                iv: iv,
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.Pkcs7
            });
            const payload = enc.toString();
            const ivB64 = CryptoJS.enc.Base64.stringify(iv);
            const signature = CryptoJS.HmacSHA256(ivB64 + ':' + payload, this.key).toString(CryptoJS.enc.Hex);
            return { encrypted: true, payload, iv: ivB64, signature };
        } catch (e) {
            return dataObj;
        }
    },

    decode(pkg) {
        this.init();
        if (!pkg || typeof pkg !== 'object' || !pkg.encrypted || !this.key || !window.CryptoJS) {
            return pkg;
        }
        try {
            const cipherParams = CryptoJS.lib.CipherParams.create({
                ciphertext: CryptoJS.enc.Base64.parse(pkg.payload)
            });
            const dec = CryptoJS.AES.decrypt(cipherParams, this.key, {
                iv: CryptoJS.enc.Base64.parse(pkg.iv),
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.Pkcs7
            });
            return JSON.parse(dec.toString(CryptoJS.enc.Utf8));
        } catch (e) {
            return pkg;
        }
    },

    async request(action, body = null, method = 'POST') {
        this.init();
        const headers = {
            'Content-Type': 'application/json',
            'X-Encrypt-Response': '1'
        };

        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const options = { method, headers };
        if (body && method === 'POST') {
            options.body = JSON.stringify(this.encode({ ...body, action }));
        }

        const res = await fetch(`api/auth.php?action=${encodeURIComponent(action)}`, options);
        const text = await res.text();
        let raw;
        try {
            raw = JSON.parse(text);
        } catch (e) {
            throw new Error(`El servidor respondió con estado ${res.status} (no JSON).`);
        }
        return this.decode(raw);
    }
};
