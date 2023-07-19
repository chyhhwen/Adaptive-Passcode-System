<?php
    include "./lib/router.php";
    $router = new router();
    echo'
     <html>
     <head>
        <link rel="stylesheet" href=".\public\index.css">
        <meta charset="UTF-8">
        <title>ChiXiao</title>
     </head>
    ';
    echo $router->get(@$_SERVER['REQUEST_URI']);
    echo '</html>';
?>