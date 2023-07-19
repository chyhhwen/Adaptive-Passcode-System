<?php
    date_default_timezone_set('Asia/Taipei');

    include "./lib/router.php";
    include "./lib/http.php";
    include "./lib/database.php";
    $router = new router();
    $http = new http();
    $sql = new sql();

    echo'
         <html>
            <head>
            <link rel="stylesheet" href=".\public\index.css">
            <meta charset="UTF-8">
            <title>ChiXiao</title>
         </head>
    ';
    $sql -> config("root","","temp","list");
    $sql -> put_data(["id","ip","time"]);
    if($sql->check($http->client_ip()))
    {
       http_response_code(404);
        echo $sql->check($http->client_ip());
        echo require "./views/error.php";
        die();
    }
    else
    {
        echo $router->get(@$_SERVER['REQUEST_URI']);
    }
    echo '</html>';
?>