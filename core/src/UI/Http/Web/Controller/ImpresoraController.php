<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Entity\DatosEmpresa;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/impresora')]
class ImpresoraController extends AbstractController
{
    public function __construct(
        private string $printerName,
        private EntityManagerInterface $em
    ) {}

    #[Route('/estado', methods: ['GET'])]
    public function estado(): JsonResponse
    {
        $empresa = $this->em->getRepository(DatosEmpresa::class)->findOneBy([]);
        if ($empresa && !$empresa->isImpresoraActiva()) {
            return new JsonResponse([
                'disponible' => true,
                'habilitada' => false,
                'impresora' => $this->printerName,
                'mensaje' => 'Impresión automática desactivada',
            ]);
        }

        $process = new Process(['lpstat', '-p', $this->printerName]);
        $process->setTimeout(5);
        $process->run();

        $disponible = $process->isSuccessful();
        return new JsonResponse([
            'disponible' => $disponible,
            'habilitada' => true,
            'impresora' => $this->printerName,
            'mensaje' => $disponible ? 'Impresora disponible' : 'La impresora ' . $this->printerName . ' no está disponible',
        ], $disponible ? 200 : 503);
    }
}
