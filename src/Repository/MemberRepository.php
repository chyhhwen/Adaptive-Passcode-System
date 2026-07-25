<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Member lookups and registration.
 *
 * The old sql::login_check() ran "SELECT * FROM menber" and compared every row
 * in PHP. Besides ignoring indexes, that meant one bcrypt verification per
 * stored user on every single login attempt.
 */
final class MemberRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `name`, `user`, `pass` FROM `member` WHERE `user` = ? LIMIT 1'
        );
        $statement->execute([$username]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Returns the member row on success, or null when the credentials are wrong.
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $member = $this->findByUsername($username);

        if ($member === null) {
            // Spend roughly the same time as a real verification so that the
            // response time does not reveal whether the username exists.
            password_verify($password, '$2y$10$usesomesillystringforsaltusesomesillystringfore');

            return null;
        }

        if (!password_verify($password, (string) $member['pass'])) {
            return null;
        }

        // Transparently upgrade hashes when the default cost or algorithm moves.
        if (password_needs_rehash((string) $member['pass'], PASSWORD_DEFAULT)) {
            $this->updatePasswordHash((int) $member['id'], $password);
        }

        return $member;
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function create(string $name, string $username, string $password): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `member` (`name`, `user`, `pass`, `time`) VALUES (?, ?, ?, ?)'
        );

        return $statement->execute([
            $name,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s'),
        ]);
    }

    private function updatePasswordHash(int $id, string $plainPassword): void
    {
        $statement = $this->pdo->prepare('UPDATE `member` SET `pass` = ? WHERE `id` = ?');
        $statement->execute([password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
    }
}