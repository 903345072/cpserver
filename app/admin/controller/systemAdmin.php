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
        $pwd = md5($pwd);
        $model = \app\admin\model\systemAdmin::find($data->userid);
        $model->pwd = $pwd;
        $model->save();
        if($gonggao){
            Db::table("eb_system_config")->where("menu_name","gonggao")->update(["value"=>$gonggao]);
        }
        return $this->success("");
    }
}