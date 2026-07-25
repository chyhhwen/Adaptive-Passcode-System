<?php

declare(strict_types=1);

/**
 * POST /api/picture_delete.php — delete a picture by id.
 *
 * Replaces sql_delete.php. That endpoint built its SQL by concatenating
 * $_POST['id'] into the string and then called execute() with no arguments, so
 * the prepare() call provided no protection at all. It also required no
 * authentication and allowed any origin.
 */

require __DIR__ . '/_bootstrap.php';

use App\Database;

require_method('POST');
require_authentication();
require_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null) {
    json_response(['ok' => false, 'message' => 'A numeric id is required'], 422);
}

try {
    $statement = Database::connection()->prepare('DELETE FROM `picture` WHERE `id` = ?');
    $statement->execute([$id]);

    json_response(['ok' => true, 'deleted' => $statement->rowCount()]);
} catch (Throwable $e) {
    error_log('picture_delete.php: ' . $e->getMessage());

    json_response(['ok' => false, 'message' => 'Internal error'], 500);
}