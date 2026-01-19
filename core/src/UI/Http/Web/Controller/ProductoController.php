<?php

namespace App\UI\Http\Web\Controller;

use App\Application\Handler\Producto\CreateProductoHandler;
use App\Application\Handler\Producto\ListProductosHandler;
use App\Application\Handler\Producto\UpdateProductoHandler;
use App\Application\Handler\Producto\DeleteProductoHandler;
use App\Application\Handler\Producto\GetProductoByIdHandler;
use App\Application\DTO\Request\ProductoRequestDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/productos')]
class ProductoController extends AbstractController
{
    public function __construct(
        private CreateProductoHandler $createHandler,
        private ListProductosHandler $listHandler,
        private UpdateProductoHandler $updateHandler,
        private DeleteProductoHandler $deleteHandler,
        private GetProductoByIdHandler $getByIdHandler
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $productos = $this->listHandler->handle();

        return new JsonResponse(
            array_map(fn($p) => $p->toArray(), $productos)
        );
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new ProductoRequestDTO(
            $data['nombre'] ?? '',
            (float)($data['precioActual'] ?? 0),
            (float)($data['costoActual'] ?? 0),
            $data['activo'] ?? true
        );

        $producto = $this->createHandler->handle($dto);

        return new JsonResponse(
            $producto->toArray(),
            JsonResponse::HTTP_CREATED
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $dto = new ProductoRequestDTO(
            $data['nombre'] ?? '',
            (float)($data['precioActual'] ?? 0),
            (float)($data['costoActual'] ?? 0),
            $data['activo'] ?? true
        );

        $producto = $this->updateHandler->handle($id, $dto);

        return new JsonResponse($producto->toArray());
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->deleteHandler->handle($id);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $producto = $this->getByIdHandler->handle($id);

        return new JsonResponse($producto->toArray());
    }
}
