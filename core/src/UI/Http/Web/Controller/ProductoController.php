<?php

namespace App\UI\Http\Web\Controller;

use App\Application\Handler\Producto\CreateProductoHandler;
use App\Application\Handler\Producto\ListProductosHandler;
use App\Application\Handler\Producto\UpdateProductoHandler;
use App\Application\Handler\Producto\DeleteProductoHandler;
use App\Application\DTO\Request\ProductoRequestDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/productos')]
class ProductoController
{
    public function __construct(
        private CreateProductoHandler $createHandler,
        private ListProductosHandler $listHandler,
        private UpdateProductoHandler $updateHandler,
        private DeleteProductoHandler $deleteHandler
    ) {}


    #[Route('', name: 'producto_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $productos = $this->listHandler->handle();
        $productosArray = array_map(fn($p) => $p->toArray(), $productos);
        return new JsonResponse($productosArray);
    }

    #[Route('', name: 'producto_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new ProductoRequestDTO(
            $data['nombre'] ?? '',
            (float)($data['precioActual'] ?? 0),
            (float)($data['costoActual'] ?? 0),
            $data['activo'] ?? true
        );

        $producto = $this->createHandler->handle($dto);
        return new JsonResponse($producto->toArray(), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'producto_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new ProductoRequestDTO(
            $data['nombre'] ?? '',
            (float)($data['precioActual'] ?? 0),
            (float)($data['costoActual'] ?? 0),
            $data['activo'] ?? true
        );

        $producto = $this->updateHandler->handle($id, $dto);
        return new JsonResponse($producto->toArray());
    }

    #[Route('/{id}', name: 'producto_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->deleteHandler->handle($id);
        return new JsonResponse(['message' => 'Producto eliminado correctamente']);
    }
}
