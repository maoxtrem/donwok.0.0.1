<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;



class LoginWebController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): Response
    {
       return new Response('', 204);
        throw new \LogicException('This should never be reached!');
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
