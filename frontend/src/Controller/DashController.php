<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response { return $this->render('dash/index.html.twig'); }

    #[Route('/productos', name: 'app_productos')]
    public function productos(): Response { return $this->render('productos/index.html.twig'); }

    #[Route('/ventas', name: 'app_ventas')]
    public function ventas(): Response { return $this->render('ventas/index.html.twig'); }

    #[Route('/pedidos/monitor', name: 'app_pedidos_monitor')]
    public function monitor(): Response { return $this->render('pedidos/monitor.html.twig'); }

    #[Route('/pedidos/gestion', name: 'app_pedidos_gestion')]
    public function gestion(): Response { return $this->render('pedidos/gestion.html.twig'); }
}