<?php

namespace App\Application\Handler\Pedido;

use App\Application\DTO\Request\PedidoRequestDTO;
use App\Domain\Entity\Factura;
use App\Domain\Repository\ProductoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class CreatePedidoHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductoRepositoryInterface $productoRepo
    ) {}

    public function handle(PedidoRequestDTO $dto): Factura
    {
        $factura = new Factura();
        $factura->setEsPago($dto->esPago);
        $factura->setTipo($dto->tipo);

        foreach ($dto->items as $itemData) {
            $cantidad = (int)($itemData['qty'] ?? 0);
            if ($cantidad < 1) {
                throw new \InvalidArgumentException('La cantidad del producto debe ser mayor que 0.');
            }

            if (($itemData['temporal'] ?? false) === true) {
                $nombre = trim((string)($itemData['nombre'] ?? ''));
                $precio = (float)($itemData['precioActual'] ?? 0);
                $costo = (float)($itemData['costoActual'] ?? 0);

                if ($nombre === '' || $precio <= 0 || $costo < 0) {
                    throw new \InvalidArgumentException('Los datos del producto temporal no son válidos.');
                }

                $factura->agregarItem($nombre, $precio, $costo, $cantidad);
                continue;
            }

            if (!isset($itemData['id'])) {
                throw new \InvalidArgumentException('El producto requiere un id o debe marcarse como temporal.');
            }

            $producto = $this->productoRepo->buscarPorId((int)$itemData['id']);
            if (!$producto) {
                throw new \InvalidArgumentException('Producto no encontrado.');
            }

            $factura->agregarItem(
                $producto->getNombre(),
                $producto->getPrecioActual(),
                $producto->getCostoActual(),
                $cantidad
            );
            $producto->incrementarScore($cantidad);
        }

        $this->em->persist($factura);
        $this->em->flush();
        
        return $factura;
    }
}
