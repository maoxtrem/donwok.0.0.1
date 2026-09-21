<?php

namespace App\Infrastructure\Mercure;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

/**
 * Genera los JWT de publicación con un issuer explícito para Mercure 1.x.
 */
final class MercureTokenFactory implements TokenFactoryInterface
{
    private Configuration $configuration;

    public function __construct(
        string $secret,
        private readonly string $issuer,
        private readonly string $audience,
    )
    {
        $this->configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret),
        );
    }

    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string
    {
        $additionalClaims['iss'] = $this->issuer;
        $additionalClaims['aud'] = $this->audience;

        $builder = $this->configuration->builder()
            ->withHeader('typ', 'at+jwt')
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->expiresAt(new \DateTimeImmutable('+1 hour'))
            ->withClaim('mercure', [
                'subscribe' => $this->normalizeMatchers($subscribe),
                'publish' => $this->normalizeMatchers($publish),
            ]);

        foreach ($additionalClaims as $name => $value) {
            if ('iss' === $name || 'aud' === $name) {
                continue;
            }

            $builder = $builder->withClaim($name, $value);
        }

        return $builder
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();
    }

    private function normalizeMatchers(?array $matchers): ?array
    {
        if (null === $matchers) {
            return null;
        }

        return array_map(
            static fn (mixed $matcher): mixed => is_string($matcher)
                ? ['match' => $matcher]
                : $matcher,
            $matchers
        );
    }
}
