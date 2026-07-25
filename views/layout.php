<?php

declare(strict_types=1);

use App\View;

/** @var string $title */
/** @var string $content */
/** @var array<int, string> $styles */
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title) ?></title>
    <link rel="stylesheet" href="/public/index.css">
    <?php foreach (($styles ?? []) as $style): ?>
        <link rel="stylesheet" href="<?= View::e($style) ?>">
    <?php endforeach; ?>
</head>
<body>
<?= $content ?>
<script src="/public/index.js" defer></script>
</body>
</html>