<?php


namespace App\UI\Http\Controller;


use App\Infrastructure\Security\TokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Domain\Repository\UserRepositoryInterface;

class AuthController
{

    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        TokenService $tokenService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username'], $data['password'])) {
            return new JsonResponse(['error' => 'JSON inválido'], 400);
        }

        // 1️⃣ Buscar usuario
        $user = $userRepository->findOneBy(['username' => $data['username']]);
        if (!$user) {
            return new JsonResponse(['error' => 'Credenciales inválidas'], 401);
        }

        // 2️⃣ Validar password
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Credenciales inválidas'], 401);
        }

        // 3️⃣ Generar token
        $token = $tokenService->sign([
            'uid' => $user->getId(),
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles()?$user->getRoles():['ROLE_USER'],
        ]);

        // 4️⃣ Devolver JSON
        return new JsonResponse(['token' => $token]);
    }
}
