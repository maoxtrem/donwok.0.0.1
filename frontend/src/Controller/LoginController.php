<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function index(): Response
    {
        return $this->render('login/index.html.twig');
    }

    #[Route('/puestos-trabajo', name: 'app_puestos_trabajo')]
    public function puestosTrabajo(): Response
    {
        return $this->render('puestos_trabajo/index.html.twig');
    }

    #[Route('/gestion/puestos-trabajo', name: 'app_gestion_puestos_trabajo')]
    public function gestionPuestosTrabajo(): Response
    {
        return $this->render('puestos_trabajo/gestion.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // El logout se maneja por JS eliminando el token del localStorage
    }
}
