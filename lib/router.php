<?php
class router
{
    public $user;
    public $pass;
    public function get($url)
    {
        switch($url)
        {
            case '/':
                return "";
            default:
                return require "./views/error.php";
        }
    }
    public function check()
    {

    }
    public function ref($a)
    {
        header('refresh:0;url="'.$a.'"');
    }
}
?>