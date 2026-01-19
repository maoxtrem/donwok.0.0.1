<?php

namespace App\Domain\Repository;

use App\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function guardar(User $user): void;
    public function buscarPorId(int $id): ?User;
    public function buscarPorUsername(string $username): ?User;
    public function existeConUsername(string $username): bool;
    public function buscarTodos() : array;
}
