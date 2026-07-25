<?php

declare(strict_types=1);

use App\View;

/** @var App\View $view */
/** @var array<int, array{id: mixed, name: mixed, data: mixed}> $pictures */
?>
<body>
<?= $view->render('nav') ?>
<div class="box">
    <div class="pic_box">
        <?php foreach (($pictures ?? []) as $picture): ?>
            <div class="pic">
                <img src="<?= View::e($picture['data']) ?>" alt="<?= View::e($picture['name']) ?>">
            </div>
        <?php endforeach; ?>

        <?php if (empty($pictures)): ?>
            <p>目前沒有相片。</p>
        <?php endif; ?>
    </div>
</div>
<footer></footer>
</body>