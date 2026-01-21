<?php

namespace App\Domain\Repository;

use App\Domain\Entity\CategoriaFinanciera;

interface CategoriaFinancieraRepositoryInterface
{
    public function buscarPorId(int $id): ?CategoriaFinanciera;
    public function buscarPorNombre(string $nombre): ?CategoriaFinanciera;
    
    /** @return CategoriaFinanciera[] */
    public function listarTodas(): array;
}
