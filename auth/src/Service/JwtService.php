<?php

namespace App\Service;

class JwtService
{
    private string $privateKey;

    public function __construct()
    {
        $this->privateKey = file_get_contents(__DIR__.'/../../config/jwt/private.pem');
    }

    public function generate(array $payload): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $h = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $p = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $data = "$h.$p";

        openssl_sign($data, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        $s = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$data.$s";
    }
}
