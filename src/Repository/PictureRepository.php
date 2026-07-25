<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PictureRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT `id`, `name`, `data` FROM `picture`');

        return $statement === false ? [] : $statement->fetchAll();
    }
}