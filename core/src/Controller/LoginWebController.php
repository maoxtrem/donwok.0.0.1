<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;


class LoginWebController extends AbstractController
{
    #[Route('/login', methods: ['POST'])]
    public function login(Request $request, UserProviderInterface $provider): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $provider->loadUserByIdentifier($data['username']);

        if ($data['password'] !== 'admin') {
            return new JsonResponse(['error' => 'invalid credentials'], 401);
        }

        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $this->container->get('security.token_storage')->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        return new JsonResponse(['status' => 'logged_in']);
    }


    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return new JsonResponse([
            'user' => $this->getUser()->getUserIdentifier(),
            'roles' => $this->getUser()->getRoles()
        ]);
    }
}
