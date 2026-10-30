<?php

class test
{
     public  static function  check(int $age, string $name,string $c):string {
     echo $age." ".$name;

     $a = $age * 4;
     echo $a;
     return " <=returned value";
    }
    public function check_2(int $age, string $name):string
    {
        return 1;
    }
}
