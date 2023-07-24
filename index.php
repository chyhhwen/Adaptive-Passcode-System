<?php
    date_default_timezone_set('Asia/Taipei');
    session_set_cookie_params(0,'/','localhost');
    session_start();
    include "./lib/router.php";
    include "./lib/http.php";
    include "./lib/database.php";
    include "./lib/txt.php";

    $router = new router();
    $http = new http();
    $sql = new sql();
    $txt = new txt();

    $time = $http -> time();

    $sql -> config("root","","temp","list");
    $sql -> put_data(["id","ip","time"]);

    if($sql->check($http->client_ip()))
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
        if(@$_SESSION['index'])
        {
            echo
            '
                <html>
                    <head>
                    <link rel="stylesheet" href="./public/index.css">
                    <meta charset="UTF-8">
                    <title>ChiXiao</title>
                </head>
            ';
        }
        else
        {
            echo
            '
                <html>
                    <head>
                    <link rel="stylesheet" href="./public/index.css">
                    <script type="text/javascript" src="./public/index.js"></script> 
                    <meta charset="UTF-8">
                    <title>ChiXiao</title>
                </head>
            ';
            echo $router->get(@$_SERVER['REQUEST_URI']);
        }
    }

    echo
    '
        </html>
    ';
?>