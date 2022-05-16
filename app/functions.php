<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use support\Request;

/**
 * Here is your custom functions.
 */
function getAdmin(Request $request){

    $token = $request->header("token","");
    $key = '344'; //key要和签发的时候一样
    $decoded = JWT::decode($token,new Key($key, 'HS256')); //HS256方式，这里要和签发的时候对应
    $arr = (array)$decoded;
    return $arr["data"];
}

function getUser(Request $request){

    $token = $request->header("bear_token","");
    $key = '123456'; //key要和签发的时候一样
    $decoded = JWT::decode($token,new Key($key, 'HS256')); //HS256方式，这里要和签发的时候对应
    $arr = (array)$decoded;
    return $arr["data"];
}

function getAgeByBirth($birth_year,$birth_month,$birth_day){

    if(empty($birth_year) || empty($birth_month) || empty($birth_day)){

        return 0;

    }

    $current_year = date('Y',time());

    $current_month = date('m',time());

    $current_day = date('d',time());

    if($birth_year >= $current_year){

        return 0;

    }

    $age = $current_year - $birth_year - 1;

    if($current_month>$birth_month){

        return $age+1;

    }else if($current_month == $birth_month && $current_day>=$birth_day){

        return $age+1;

    }else{

        return $age;

    }

}