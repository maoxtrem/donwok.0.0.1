<?php

namespace App\Infrastructure\Security;

class TokenService
{
    public function __construct(private string $privateKeyPath) {}

    /**
     * Genera un JWT estándar RS256
     *
     * @param array $payload Datos del token (uid, username, roles, etc.)
     * @param int $exp Tiempo de expiración en segundos (default: 1 hora)
     * @return string JWT
     */
    public function sign(array $payload, int $exp = 3600): string
    {
        // 1️⃣ Header
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        // 2️⃣ Payload: agregar fecha de expiración
        $payload['exp'] = time() + $exp;

        // 3️⃣ Encode header y payload en Base64Url
        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));

        // 4️⃣ Concatenar para firmar
        $dataToSign = $base64Header . '.' . $base64Payload;

        // 5️⃣ Firmar con clave privada
        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));
        if (!$privateKey) {
            throw new \RuntimeException('No se pudo cargar la clave privada');
        }

        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $base64Signature = $this->base64UrlEncode($signature);

        // 6️⃣ Retornar JWT
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
