<?php
class router
{
    public $user;
    public $pass;
    public function get($url)
    {
        if(@$this->check())
        {
            $temp = str_split($_SERVER['REQUEST_URI'],5);
            $use = "";
            for($i=1;$i<count($temp);$i++)
            {
                $use = $use.$temp[$i];
            }
            return $use;
        }
        else
        {
            switch($url)
            {
                case '/':
                    return "";
                default:
                    return require "./views/error.php";
            }
        }

    }
    public function check()
    {
        $temp = str_split($_SERVER['REQUEST_URI'],5);
        if($temp[0] == "/api/")
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function ref($a)
    {
        header('refresh:0;url="'.$a.'"');
    }
}
?>