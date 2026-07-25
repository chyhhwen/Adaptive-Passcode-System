<?php

declare(strict_types=1);

use App\Csrf;
use App\View;

/** @var string|null $error */
?>
<body>
<div class="login-page">
    <?php if (!empty($error)): ?>
        <p class="error-message"><?= View::e($error) ?></p>
    <?php endif; ?>

    <form action="/login" method="post">
        <?= Csrf::field() ?>
        <div class="put" style="display:block">
            <span>帳號</span>
            <input type="text" name="user" id="user" maxlength="32" required>
            <span>密碼</span>
            <input type="password" name="pass" id="pass" required>
            <input type="submit" value="登入">
            <input type="button" value="註冊" id="show-register">
        </div>
    </form>

    <form action="/register" method="post">
        <?= Csrf::field() ?>
        <div class="put1" style="display:none" id="register-panel">
            <span>名稱</span>
            <input type="text" name="name1" id="name1" maxlength="32" required>
            <span>帳號</span>
            <input type="text" name="user1" id="user1" maxlength="32" required>
            <span>密碼</span>
            <input type="password" name="pass1" id="pass1" minlength="8" required>
            <span>確認</span>
            <input type="password" id="repass1" minlength="8" required>
            <input type="submit" value="註冊">
            <input type="button" value="返回" id="show-login">
        </div>
    </form>
</div>
</body>