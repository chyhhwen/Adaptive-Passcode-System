<?php

declare(strict_types=1);

use App\Csrf;
use App\Session;
use App\View;
?>
<nav>
    <span>CHIXIAO</span>
    <a href="/">Home</a>
    <a href="/photo">Photo</a>
    <a href="/about">About</a>
    <?php if (Session::isAuthenticated()): ?>
        <span class="user">您好，<?= View::e(Session::userName()) ?></span>
        <form action="/logout" method="post" class="logout-form">
            <?= Csrf::field() ?>
            <button type="submit">登出</button>
        </form>
    <?php endif; ?>
</nav>