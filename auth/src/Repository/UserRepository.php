<?php

namespace App\Repository;

use App\Infrastructure\Database\PdoConnection;

class UserRepository
{
    public function __construct(
        private PdoConnection $connection
    ) {}

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->connection->get()->prepare(
            'SELECT id, username, password FROM user WHERE username = :username'
        );

        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch();

        return $user ?: null;
    }
}
