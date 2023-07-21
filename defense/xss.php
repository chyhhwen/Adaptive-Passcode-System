<?php
    if($_POST['login'])
    {
        var_dump($_POST['user'],$_POST['pass']);
    }
    $password = '1111'; //假設使用者輸入的密(明)碼是 1111
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    echo $password_hash;

?>