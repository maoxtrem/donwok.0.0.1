<?php

namespace App\Infrastructure\Security;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AuthServiceTokenProvider
{
    private const CACHE_KEY = 'auth.service_token';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $authBaseUrl,
        private string $serviceName,
        private string $serviceSecret
    ) {}

    public function getToken(): string
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $response = $this->httpClient->request('POST', $this->authBaseUrl . '/service/login', [
                'json' => [
                    'service' => $this->serviceName,
                    'secret' => $this->serviceSecret,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('No se pudo autenticar contra Auth');
            }

            $data = $response->toArray();
            $token = $data['token'] ?? null;
     
            if (!$token) {
                throw new \RuntimeException('Auth no devolvió token');
            }

            // ⏱ calcular expiración del JWT
            $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
            $ttl = ($payload['exp'] ?? time() + 300) - time() - 60;

            $item->expiresAfter(max(60, $ttl));

            return $token;
        });
    }
}
