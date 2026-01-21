<?php

namespace App\UI\Cli;

use App\Domain\Entity\CategoriaFinanciera;
use App\Domain\Entity\CuentaFinanciera;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-finance',
    description: 'Inicializa datos financieros básicos (Categorías y Cuentas)',
)]
class SeedFinanceCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Categoría Ventas Diarias
        $repoCat = $this->em->getRepository(CategoriaFinanciera::class);
        $categorias = [
            ['Ventas Diarias', 'INGRESO', 'Consolidado de ventas por cierre de caja'],
            ['Inversiones', 'EGRESO', 'Compra de activos o inversión de capital'],
            ['Gastos Pasivos', 'EGRESO', 'Gastos fijos, servicios, etc.'],
            ['Control de salidas', 'EGRESO', 'Salidas de dinero diversas'],
            ['Préstamos Otorgados', 'EGRESO', 'Dinero prestado a terceros'],
            ['Cobro de Préstamo', 'INGRESO', 'Recuperación de cartera']
        ];

        foreach ($categorias as [$nombre, $tipo, $desc]) {
            if (!$repoCat->findOneBy(['nombre' => $nombre])) {
                $cat = new CategoriaFinanciera($nombre, $tipo, $desc);
                $this->em->persist($cat);
                $io->info("Categoría \"$nombre\" creada.");
            }
        }

        // Cuenta Caja Principal (Efectivo)
        $repoCuenta = $this->em->getRepository(CuentaFinanciera::class);
        if (!$repoCuenta->findOneBy(['nombre' => 'Caja Principal'])) {
            $cuenta = new CuentaFinanciera('Caja Principal', 'CAJA', 0);
            $this->em->persist($cuenta);
            $io->info('Cuenta "Caja Principal" creada.');
        }

        // Cuenta Nequi (Banco)
        if (!$repoCuenta->findOneBy(['nombre' => 'Cuenta Nequi'])) {
            $cuenta = new CuentaFinanciera('Cuenta Nequi', 'BANCO', 0);
            $this->em->persist($cuenta);
            $io->info('Cuenta "Cuenta Nequi" creada.');
        }

        $this->em->flush();
        $io->success('Datos financieros inicializados.');

        return Command::SUCCESS;
    }
}
