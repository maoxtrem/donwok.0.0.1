<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\PasswordService;
use App\Service\JwtService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LoginController
{
    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $users,
        PasswordService $passwords,
        JwtService $jwt
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = $users->findByUsername($data['username'] ?? '');

        if (
            !$user ||
            !$passwords->verify($data['password'] ?? '', $user['password'])
        ) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        return new JsonResponse([
            'token' => $jwt->generate([
                'sub' => $user['id'],
                'username' => $user['username'],
                'iat' => time(),
                'exp' => time() + 3600,
            ])
        ]);
    }
}

