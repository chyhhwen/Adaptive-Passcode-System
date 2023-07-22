<?php
    include "../lib/database.php";
    $sql = new sql();
    $u = $_POST['user'];
    $p = $_POST['pass'];
    $sql -> config("root","","temp","menber");
    $sql -> put_data(["id","name","user","pass","time"]);

    if($sql->login_check($u,$p))
    {
        echo '登入成功';
        //header('Location: http://localhost/');
    }
    else
    {
        header('Location: http://localhost/');
    }
?>