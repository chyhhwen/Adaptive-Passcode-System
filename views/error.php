<?php

declare(strict_types=1);

use App\View;

/** @var int $status */
/** @var string $message */
?>
<div class="error-page">
    <div>
        <h1><?= View::e($status ?? 404) ?></h1>
        <p><?= View::e($message ?? 'NOT FOUND') ?></p>
        <a href="/">回首頁</a>
    </div>
</div>