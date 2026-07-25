<?php

declare(strict_types=1);

use App\View;

/** @var App\View $view */
?>
<?= $view->render('nav') ?>
<div class="box">
    <h1>歡迎回來，<?= View::e($userName ?? '') ?></h1>
</div>
<footer></footer>