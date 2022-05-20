<?php
namespace app\admin\controller;

use app\api\controller\Base;
use support\Db;
use support\Request;

class systemAdmin extends Base{

    public function savePwd(Request $request){
        $data = getAdmin($request);
        $pwd= $request->input("pwd","");
        $gonggao = $request->input("gonggao","");
        $u_pwd = $request->input("u_pwd","");
        $u_account = $request->input("u_account","");
        $u_name = $request->input("u_name","");
        if ($pwd){
            $pwd = md5($pwd);
            $model = \app\admin\model\systemAdmin::find($data->userid);
            $model->pwd = $pwd;
            $model->save();
        }
        if($gonggao){
            Db::table("eb_system_config")->where("menu_name","gonggao")->update(["value"=>$gonggao]);
        }
        if($u_name && $u_pwd && $u_account){
            Db::table("eb_user")->insert([
                "account"=>$u_account,
                'phone'=>$u_account,
                'pwd'=>md5($u_pwd),
                'real_name'=>$u_name,
                'nickname'=>$u_name,
                'avatar'=>'/avatar/1652706191718578.jpeg',
                'is_moni'=>1,
                'is_seller'=>1,
                'add_time'=>time(),
                'five_grade'=>serialize([]),
                'invite_code'=>rand(10000,99999)
            ]);
        }
        return $this->success("");
    }
}