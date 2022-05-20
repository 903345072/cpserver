<?php

namespace app\admin\controller;


use app\api\controller\Base;
use app\api\model\bill;
use app\api\model\footGame;

use Illuminate\Database\QueryException;
use support\Request;
use support\Db;
use Webman\Config;

class User extends Base
{
    public function userinfo(Request $request)
    {
        $uid = $request->input("uid",1);
        $arr = \app\api\model\user::with("orders")->find($uid)->toArray();
        return $this->success($arr);
    }

    public function setDashen(Request $request){

        $user = \app\api\model\user::find($request->input("uid",1));
        $user->is_dashen = $request->input("value");
        $res = $user->save();
        return $this->success($res);
    }

    public function setMoni(Request $request){
        $user = \app\api\model\user::find($request->input("uid",1));
        $user->is_moni = $request->input("value");
        $res = $user->save();
        return $this->success($res);
    }

    public function setSeller(Request $request){

        $user = \app\api\model\user::find($request->input("uid",1));
        $user->is_seller = $request->input("value");
        $res = $user->save();
        return $this->success($res);
    }
    public function setDashenOrder(Request $request){

        $user = \app\api\model\user::find($request->input("uid",1));
        $user->dashen_order = $request->input("value");
        $res = $user->save();
        return $this->success($res);
    }
    public function setYjRate(Request $request){

        $user = \app\api\model\user::find($request->input("uid",1))->f;
        $user->yj_rate = $request->input("value");
        $res = $user->save();
        return $this->success($res);
    }

    public function changeMoney(Request $request){
        DB::beginTransaction();
        try {
            $user = \app\api\model\user::where("uid",$request->input("uid",1))->lockForUpdate()->get();
            $user = $user[0];
            $value = $request->input("value",0);
            $mark = $request->input("mark","");
            $user->now_money = $user->now_money + $value;
            $res1 = $user->save();
            $pm = $value>0?1:0;
            $title = $value>0?"店主加款":"店主扣款";
            $mark = $mark?$mark:$title;
            bill::addBill($user->uid,$user->uid,$pm,$title,"now_money","system",$value,$user->now_money,$mark);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return $this->error("",$e->getMessage());
        }
        return $this->success($user);
    }

    public function userList(Request $request)
    {
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $nickname =  $request->input("nickname","");
        $model = \app\api\model\user::orderBy("uid","desc");
        if ($nickname){
            $model = $model->where('nickname',$nickname)->orWhere('nickname','like','%'.$nickname.'%');
        }
        $arr = $model->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success($arr);
    }

    public function searchUser(Request $request)
    {
        $nickname =  $request->input("nickname","");
        $arr = \app\api\model\user::where('nickname',$nickname)->orWhere('nickname','like','%'.$nickname.'%')->get()->toArray();
        return $this->success($arr);
    }

    public function getBillList(Request $request){
        $uid = $request->input("uid","1");
        $type = $request->input("type","buy_lottery");
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);

        $time = $request->input("range","");
        $model = new bill();
        $model = $model->where("type",$type)->where("uid",$uid);
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("add_time",$time);
        }
        $clone_model = clone $model;
        if($type == "withdraw"){
            $clone_model =  $clone_model->where("status",1);
        }
        $total = $clone_model->sum("number");
        $data = $model->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();

        return $this->success(["data"=>$data,"total"=>$total]);
    }

    public function orderRecord(Request $request)
    {
        $uid = $request->input("uid",1);
        $time = $request->input("range","");
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = \app\api\model\Order::with("orderDetails")->with("user")->where("uid",$uid);
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("order_time",$time);
        }
        $data = $model->orderBy("order_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        \app\admin\model\Order::setOrderData($data);
        return $this->success($data);
    }

    public function registerList(Request $request)
    {
        $uid = $request->input("uid",1);
        $time = $request->input("range","");
        $name = $request->input("user","");
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = \app\api\model\user::where("pid",$uid);
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("add_time",$time);
        }

        if($name){
            $model = $model->where(function ($q)use($name){
                $q->orWhere('phone','like','%'.$name."%");
                $q->orWhere('phone',$name);
                $q->orWhere('nickname','like','%'.$name."%");
                $q->orWhere('nickname',$name);
            });
        }

        $count = $model->count();
        $data = $model->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(["data"=>$data,"count"=>$count]);
    }
    public function betList(Request $request)
    {
        $uid = $request->input("uid",1);
        $uids =  \app\api\model\user::where("pid",$uid)->get()->toArray();
        $uids = array_column($uids,"uid");
        $name = $request->input("user","");
        $time = $request->input("range","");
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = \app\api\model\Order::whereIn("uid",$uids);
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("order_time",$time);
        }
        $model = $model->with("user")->whereHas("user",function ($q)use($name){
            if($name){
                $q->where(function ($q)use($name){
                    $q->orWhere('phone','like','%'.$name."%");
                    $q->orWhere('phone',$name);
                    $q->orWhere('nickname','like','%'.$name."%");
                    $q->orWhere('nickname',$name);

                });
            }
        });

        $clone_model = clone $model;
        $bet_amount = $clone_model->whereIn("state",[0,1,2,3])->sum("amount");
        $data = $model->orderBy("order_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(["data"=>$data,"bet_amount"=>$bet_amount]);
    }
    public function test(Request $request)
    {


        $name = footGame::getUnstartGames();
        return $this->success($name);

    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
