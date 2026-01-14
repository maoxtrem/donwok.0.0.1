<?php

namespace App\Service;

class TokenVerifierService
{
    public function __construct(private string $publicKeyPath) {}

    /**
     * Verifica un token JWT y devuelve el payload decodificado si es válido
     * @param string $token
     * @return array|null
     */
    public function verify(string $token): ?array
    {
       
        if (empty($token)) {
            return null;
        }

        // JWT estándar = header.payload.signature
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $dataToVerify = $encodedHeader . '.' . $encodedPayload;
        $signature = $this->base64UrlDecode($encodedSignature);

        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));
        if (!$publicKey) {
            throw new \RuntimeException("No se pudo cargar la clave pública");
        }

        $ok = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($ok === 1) {
            // Token válido, devolver payload decodificado
            return json_decode($this->base64UrlDecode($encodedPayload), true);
        }

        return null; // Firma inválida
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
