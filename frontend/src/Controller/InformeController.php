<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/informes')]
class InformeController extends AbstractController
{
    #[Route('', name: 'app_informes_index')]
    public function index(): Response
    {
        return $this->render('informes/index.html.twig');
    }

    #[Route('/balance', name: 'app_informes_balance')]
    public function balance(): Response
    {
        return $this->render('informes/balance.html.twig');
    }
}
