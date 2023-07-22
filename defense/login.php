<?php
    include "../lib/database.php";
    $sql = new sql();
    $u = $_POST['user'];
    $p = $_POST['pass'];
    $sql -> config("root","","temp","list");
    $sql -> put_data(["id","ip","time"]);
    /*if($sql->login_check())
    {
        http_response_code(404);
        echo $sql->check($http->client_ip());
        echo require "./views/error.php";
        die();
        $txt -> put_test("嘗試進入");
        $txt -> write();
    }
    else
    {
        
    }*/
    echo $sql->login_check();
?>