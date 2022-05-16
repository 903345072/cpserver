<?php

namespace app\api\controller;


use app\api\model\bill;
use app\api\model\footGame;

use app\api\model\orderDetail;
use app\api\service\OrderFactory;
use support\Request;

use Webman\Config;
use app\api\model\Order as OrderModel;
use support\Db;

class Order extends Base
{
    public function doOrder(Request $request)
    {



        $data= $request->all();

        $model = new OrderModel($data);
        $check_game = json_decode($data["checkGame"],1);
        $num_arr = json_decode($data["num_arr"],1);

        $order_factory = new OrderFactory($data["type"]);
        $stop_time = $order_factory->createOrderServiceImpl()->getOrderStopTime($check_game);
        $chuan = array_column(json_decode($data["chuan"],1),"value");
        $chuan = array_unique($chuan);
        $model->chuan = implode(",",$chuan);
        $model->uid = getUser($request)->userid;



        if($data["mode"] == 4){
            $num = 0;
            foreach ($check_game as $k=>$v){
                $num += $num_arr[$k]["zhu"];
            }
            $model->amount = $num*2;
            $model->bei_amount = $num*2;
            $model->num = $num;
        }else{
            $model->amount = $data["num"]*2*$data["bei"];
            $model->bei_amount = $data["num"]*2;
        }


        $model->order_time = time();
        $model->order_no =  date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $model->state = -1;
        $model->stop_time = strtotime($stop_time);
        if(!in_array($data["type"],["foot","basket","pl3","bd"])){
            return $this->error("","未开放");
        }
        DB::beginTransaction();
        try {
            $user = \app\api\model\user::where("uid",$model->uid)->lockForUpdate()->first();
            if($user->award_amount + $user->now_money < $model->amount){
                DB::rollback();
                return $this->error("","预存不足");
            }
           //主订单
           $res = $order_factory->createOrderServiceImpl()->create($model);
           //订单详情1
            $arr = [];
            foreach ($check_game as $k=>$v){
                if ($data["type"] == "foot" || $data["type"] == "basket" || $data["type"] == "bd"){
                    foreach ($v as $k1=>&$v1){
                        $v1["qcbf"] = "";
                        $v1["bcbf"] = "";
                        $v1["ret"] = 0;
                    }
                }
                $arr[] = ["order_id"=>$model->id,"bet_content"=>serialize($v),"num"=>$data["mode"]==1 || $data["mode"]==2?$data["bei"]:$num_arr[$k]["zhu"]];
            }
           $res1 = DB::table('eb_order_detail')->insert($arr);
           if($user->now_money >= $model->amount){
              $user->now_money-=$model->amount;
           }else{
               $sub_money = $model->amount - $user->now_money;
               $user->now_money = 0;
               $user->award_amount -= $sub_money;
           }
           $user->save();
            bill::addBill($user->uid,$model->id,0,"购彩","now_money","buy_lottery",$model->amount,$user->now_money,"用户购彩");

           DB::commit();

        }catch (\Exception $ex){
            DB::rollback();

            return $this->error("",$ex->getMessage());
        }

        $data = \app\api\model\Order::with("orderDetails")->where("id",$model->id)->get()->toArray();
        \app\api\model\Order::setOrderData($data);
        return $this->success($data);
    }

    public function doFlowOrder(Request $request){
        $id = $request->input("id");
        $bei = $request->input("bei");
        $p_order = \app\api\model\Order::with("orderDetails")->where("id",$id)->where("stop_time",">",time())->first();
        if(!$p_order){
            return $this->error("","订单已截止");
        }
        $model = new \app\api\model\Order();

        $model->type = $p_order->type;
        $model->pid = $p_order->id;
        $model->num = $p_order->num;
        $model->chuan = $p_order->chuans;
        $model->stop_time = strtotime($p_order->stop_time);
        $model->state = -1;
        $model->uid = getUser($request)->userid;
        $model->bei = $bei;
        $model->amount = $p_order->bei_amount*$bei;
        $model->order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $model->order_time = time();
        $model->mode = 3;

        $order_factory = new OrderFactory($p_order->type);
        DB::beginTransaction();
        try {

            $user = \app\api\model\user::where("uid",$model->uid)->lockForUpdate()->first();
            if($user->award_amount + $user->now_money < $model->amount){
                DB::rollback();
                return $this->error("","预存不足");
            }
            //主订单
            $res = $order_factory->createOrderServiceImpl()->create($model);
            //订单详情1

            $arr= orderDetail::where("order_id",$id)->get()->toArray();



            foreach ($arr as $k=>&$v){
                $v["order_id"] = $model->id;
                $v["bet_content"] = serialize($v["bet_content"]);
                unset($v["id"]);

            }

            $res1 = DB::table('eb_order_detail')->insert($arr);
            if($user->now_money >= $model->amount){
                $user->now_money-=$model->amount;
            }else{
                $sub_money = $model->amount - $user->now_money;
                $user->now_money = 0;
                $user->award_amount -= $sub_money;
            }
            $user->save();
            bill::addBill($user->uid,$model->id,0,"购彩","now_money","buy_lottery",$model->amount,$user->now_money,"用户购彩");
            $p_order->flow_count = $p_order->flow_count + 1;
            $p_order->flow_amount = $p_order->flow_amount + $model->amount;
            $p_order->save();
            DB::commit();
        }catch (\Exception $ex){

            DB::rollback();
            return $this->error("",$ex->getMessage());
        }

        $data = \app\api\model\Order::with("orderDetails")->where("id",$model->id)->get()->toArray();

        \app\api\model\Order::setOrderData($data);
        return $this->success($data);

    }


    public function orderList(Request $request)
    {
        $type = $request->input("state",-1)-2;
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model =\app\api\model\Order::with("orderDetails");
        if($type != -2){
            $model = $model->where("state",$type);
        }else{

            $model = $model->where("state","<",4);
        }
        $data = $model->orderBy("order_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        \app\api\model\Order::setOrderData($data);
        return $this->success($data);
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
