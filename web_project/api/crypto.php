<?php
/**
 * Módulo de transporte de datos cliente-servidor
 */

function getTransportKey(): string {
    return hash('sha256', APP_SECURITY_KEY, true);
}

function decodeRequestPayload(?string $rawInput, bool &$wasEncoded): array {
    $wasEncoded = false;
    if (empty($rawInput)) {
        return $_POST;
    }

    $json = json_decode($rawInput, true);
    if (!is_array($json)) {
        return $_POST;
    }

    if (!empty($json['encrypted']) && !empty($json['payload']) && !empty($json['iv'])) {
        $key = getTransportKey();
        $payload = (string)$json['payload'];
        $iv = (string)$json['iv'];

        if (!empty($json['signature'])) {
            $expectedSig = hash_hmac('sha256', $iv . ':' . $payload, $key);
            if (!hash_equals($expectedSig, (string)$json['signature'])) {
                sendJson(false, 'Solicitud inválida.', null, 400);
            }
        }

        $rawCipher = base64_decode($payload);
        $rawIv = base64_decode($iv);
        if ($rawCipher === false || $rawIv === false || strlen($rawIv) !== 16) {
            sendJson(false, 'Formato de solicitud inválido.', null, 400);
        }

        $decrypted = openssl_decrypt($rawCipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $rawIv);
        if ($decrypted === false) {
            sendJson(false, 'No se pudo procesar la solicitud.', null, 400);
        }

        $data = json_decode($decrypted, true);
        if (!is_array($data)) {
            sendJson(false, 'Datos de solicitud inválidos.', null, 400);
        }

        $wasEncoded = true;
        return $data;
    }

    return $json;
}

function encodeResponsePayload(array $responseArray): array {
    $key = getTransportKey();
    $jsonPlain = json_encode($responseArray, JSON_UNESCAPED_UNICODE);
    $iv = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($jsonPlain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    $b64Payload = base64_encode($cipher);
    $b64Iv = base64_encode($iv);
    $sig = hash_hmac('sha256', $b64Iv . ':' . $b64Payload, $key);

    return [
        'encrypted' => true,
        'payload'   => $b64Payload,
        'iv'        => $b64Iv,
        'signature' => $sig,
        'timestamp' => time()
    ];
}

function sendJson(bool $ok, string $msg, $data = null, int $code = 200): void {
    global $isRequestEncoded;
    http_response_code($code);

    $response = [
        'success'   => $ok,
        'message'   => $msg,
        'timestamp' => time(),
        'data'      => $data
    ];

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $wantEnc = !empty($headers['X-Encrypt-Response'])
            || !empty($headers['x-encrypt-response'])
            || !empty($_SERVER['HTTP_X_ENCRYPT_RESPONSE']);

    if (!empty($isRequestEncoded) || $wantEnc) {
        echo json_encode(encodeResponsePayload($response), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    exit;
}
