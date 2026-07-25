<?php

declare(strict_types=1);

/**
 * GET /api/pictures.php — list pictures.
 *
 * Replaces sql_data.php, which required a config.php that does not exist in the
 * repository (so it fatally errored), opened itself to every origin, and sent a
 * second Content-Type header written with a full-width colon.
 */

require __DIR__ . '/_bootstrap.php';

use App\Database;
use App\Repository\PictureRepository;

require_method('GET');
require_authentication();

try {
    $pictures = new PictureRepository(Database::connection());

    json_response(['ok' => true, 'comments' => $pictures->all()]);
} catch (Throwable $e) {
    error_log('pictures.php: ' . $e->getMessage());

    json_response(['ok' => false, 'message' => 'Internal error'], 500);
}