<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caja')]
class CajaController extends AbstractController
{
    #[Route('', name: 'app_caja_index')]
    public function index(): Response
    {
        return $this->render('caja/index.html.twig');
    }

    #[Route('/movimientos', name: 'app_caja_movimientos_view')]
    public function movimientos(): Response
    {
        return $this->render('caja/movimientos.html.twig');
    }

    #[Route('/egresos', name: 'app_caja_egresos_view')]
    public function egresos(): Response
    {
        return $this->render('caja/egresos.html.twig');
    }

    #[Route('/prestamos', name: 'app_caja_prestamos_view')]
    public function prestamos(): Response
    {
        return $this->render('caja/prestamos.html.twig');
    }
}
