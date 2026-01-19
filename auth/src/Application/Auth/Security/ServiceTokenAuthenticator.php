<?php

namespace App\Application\Auth\Security;

use App\Infrastructure\Security\TokenVerifierService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RoleBadge;

class ServiceTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private TokenVerifierService $tokenVerifier) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {

        $header = $request->headers->get('Authorization');
        $token  = substr($header, 7);

        $payload = $this->tokenVerifier->verify($token);

        if (!$payload) {
            throw new AuthenticationException('Token inválido o expirado');
        }

        $username = $payload['username'] ?? 'SERVICE_USER';
        $roles    = $payload['roles'] ?? ['ROLE_SERVICE'];

        return new SelfValidatingPassport(
            new UserBadge(
                $username,
                fn() => new InMemoryUser($username, null, $roles)
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?\Symfony\Component\HttpFoundation\Response
    {
        return null; // continuar con la petición normalmente
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?\Symfony\Component\HttpFoundation\Response
    {
        return new JsonResponse([
            'error'   => 'No autorizado',
            'message' => $exception->getMessage(),
        ], 401);
    }
}
