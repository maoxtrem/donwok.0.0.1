<?php

namespace App\UI\Http\Web\Controller;

use App\Domain\Entity\DatosEmpresa;
use App\Domain\Entity\DeduccionCaja;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/datos-empresa')]
class DatosEmpresaController extends AbstractController
{
    #[Route('/deducciones', methods: ['GET'])]
    public function listarDeducciones(EntityManagerInterface $em): JsonResponse
    {
        $empresa = $this->obtener($em);
        $deducciones = $em->getRepository(DeduccionCaja::class)->findBy(['empresa' => $empresa], ['id' => 'ASC']);

        return new JsonResponse(array_map(fn(DeduccionCaja $d) => $d->toArray(), $deducciones));
    }

    #[Route('/deducciones', methods: ['POST'])]
    public function crearDeduccion(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $empresa = $this->obtener($em);
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $deduccion = new DeduccionCaja(
                $empresa,
                (string)($data['nombre'] ?? ''),
                (string)($data['tipo'] ?? ''),
                (float)($data['valor'] ?? 0),
                array_key_exists('activo', $data) ? (bool)$data['activo'] : true,
                array_key_exists('cadaCierre', $data) ? (bool)$data['cadaCierre'] : true,
                array_key_exists('mostrarEnCierre', $data) ? (bool)$data['mostrarEnCierre'] : true
            );
            $em->persist($deduccion);
            $em->flush();

            return new JsonResponse($deduccion->toArray(), JsonResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/deducciones/{id}', methods: ['PATCH'])]
    public function actualizarDeduccion(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $empresa = $this->obtener($em);
        $deduccion = $em->getRepository(DeduccionCaja::class)->findOneBy(['id' => $id, 'empresa' => $empresa]);
        if (!$deduccion) {
            return new JsonResponse(['message' => 'Deducción no encontrada.'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        try {
            $deduccion->actualizar(
                (string)($data['nombre'] ?? $deduccion->getNombre()),
                (string)($data['tipo'] ?? $deduccion->getTipo()),
                array_key_exists('valor', $data) ? (float)$data['valor'] : $deduccion->getValor()
            );
            if (array_key_exists('activo', $data)) {
                $deduccion->setActivo((bool)$data['activo']);
            }
            if (array_key_exists('cadaCierre', $data)) {
                $deduccion->setCadaCierre((bool)$data['cadaCierre']);
            }
            if (array_key_exists('mostrarEnCierre', $data)) {
                $deduccion->setMostrarEnCierre((bool)$data['mostrarEnCierre']);
            }
            $em->flush();

            return new JsonResponse($deduccion->toArray());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/deducciones/{id}', methods: ['DELETE'])]
    public function eliminarDeduccion(int $id, EntityManagerInterface $em): JsonResponse
    {
        $empresa = $this->obtener($em);
        $deduccion = $em->getRepository(DeduccionCaja::class)->findOneBy(['id' => $id, 'empresa' => $empresa]);
        if (!$deduccion) {
            return new JsonResponse(['message' => 'Deducción no encontrada.'], 404);
        }

        $em->remove($deduccion);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

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
        if (array_key_exists('impresoraActiva', $data)) {
            $empresa->setImpresoraActiva((bool)$data['impresoraActiva']);
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
