<?php

namespace App\Application\Handler\Finanzas;

use App\Domain\Entity\Prestamo;
use App\Domain\Entity\PagoPrestamo;
use App\Domain\Repository\CuentaFinancieraRepositoryInterface;
use App\Domain\Repository\CategoriaFinancieraRepositoryInterface;
use App\Domain\Repository\PrestamoRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegistrarDeudaHandler
{
    public function __construct(
        private PrestamoRepositoryInterface $prestamoRepo,
        private CuentaFinancieraRepositoryInterface $cuentaRepo,
        private CategoriaFinancieraRepositoryInterface $categoriaRepo,
        private EntityManagerInterface $em
    ) {}

    public function handle(array $data): array
    {
        $monto = (float)$data['monto'];
        $entidad = $data['entidad'];
        $cuentaId = (int)$data['cuenta_id'];
        $categoriaId = (int)$data['categoria_id'];
        $observaciones = $data['observaciones'] ?? '';

        $cuenta = $this->cuentaRepo->buscarPorId($cuentaId);
        $categoria = $this->categoriaRepo->buscarPorId($categoriaId);

        if (!$cuenta || !$categoria) throw new \Exception("Cuenta o Categoría no válida.");

        $deuda = new Prestamo('RECIBIDO', $monto, $entidad, $cuenta, $categoria, $observaciones);
        $this->prestamoRepo->guardar($deuda);

        // Si la categoría es 'Crédito' (entrada de capital), 
        // registramos la entrada de dinero hoy
        if ($categoria->getNombre() === 'Crédito') {
            $pagoTecnico = new PagoPrestamo($deuda, $monto, $cuenta, "Ingreso de Capital: $entidad", true);
            $this->em->persist($pagoTecnico);
            $this->em->flush();
        }

        return [
            'id' => $deuda->getId(),
            'entidad' => $deuda->getEntidad(),
            'monto' => $deuda->getMontoTotal(),
            'categoria' => $categoria->getNombre()
        ];
    }
}
