<?php

declare(strict_types=1);

use App\View;

/** @var App\View $view */
/** @var array<int, array{id: mixed, name: mixed, data: mixed}> $pictures */

// public/photo.css sizes .pic at 25% inside a flex .pic_box, so each row holds
// four. Putting every picture in a single .pic_box would squeeze them all onto
// one line.
$rows = array_chunk($pictures ?? [], 4);
?>
<?= $view->render('nav') ?>
<div class="box">
    <?php foreach ($rows as $row): ?>
        <div class="pic_box">
            <?php foreach ($row as $picture): ?>
                <div class="pic">
                    <img src="<?= View::e($picture['data']) ?>" alt="<?= View::e($picture['name']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($rows === []): ?>
        <p>目前沒有相片。執行 <code>php seed.php</code> 匯入 public/images/ 的圖片。</p>
    <?php endif; ?>
</div>
<footer></footer>