<?php
declare(strict_types = 1);
namespace app\api\controller;



class Base
{


    public function success($data,String $msg =''){
        return json(["code"=>200,"msg"=>$msg,"data"=>$data]);
    }


    public function error( $data,String $msg ){
        return json(["code"=>501,"msg"=>$msg,"data"=>$data]);
    }

}
