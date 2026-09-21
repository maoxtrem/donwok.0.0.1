<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Entity\DatosEmpresa;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/datos-empresa')]
class DatosEmpresaController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function ver(EntityManagerInterface $em): JsonResponse
    {
        return new JsonResponse($this->obtener($em)->toArray());
    }

    #[Route('', methods: ['PATCH'])]
    public function actualizar(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $empresa = $this->obtener($em);
        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('nombre', $data)) {
            $empresa->setNombre(trim((string)$data['nombre']));
        }
        if (array_key_exists('nit', $data)) {
            $empresa->setNit(trim((string)$data['nit']));
        }
        if (array_key_exists('fraseDia', $data)) {
            $frase = trim((string)$data['fraseDia']);
            $empresa->setFraseDia($frase !== '' ? $frase : null);
        }
        if (array_key_exists('telefonoDomicilios', $data)) {
            $empresa->setTelefonoDomicilios(trim((string)$data['telefonoDomicilios']));
        }

        $em->flush();
        return new JsonResponse(['message' => 'Datos de empresa guardados', 'empresa' => $empresa->toArray()]);
    }

    private function obtener(EntityManagerInterface $em): DatosEmpresa
    {
        $empresa = $em->getRepository(DatosEmpresa::class)->findOneBy([]);
        if (!$empresa) {
            $empresa = new DatosEmpresa();
            $em->persist($empresa);
            $em->flush();
        }

        return $empresa;
    }
}
