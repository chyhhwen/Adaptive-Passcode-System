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
    /**
     * A real password_hash() output, used only to burn the same amount of CPU
     * as a genuine check when the username does not exist. It corresponds to no
     * usable password.
     */
    private const DUMMY_HASH = '$2y$10$E33.1bYy0TfXGN3usvucWeZrvK/HWE8Cns5fVMAIAqHnS4zkVT5I2';

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
            //
            // This must be a well-formed 60-character bcrypt hash. A malformed
            // string happens to work today only because bcrypt reads a 22-char
            // salt and ignores the rest, so crypt() still does the full round —
            // but nothing guarantees password_verify() will keep calling crypt()
            // for input it could reject outright, and the protection would then
            // disappear with no test failing.
            password_verify($password, self::DUMMY_HASH);

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