<?php

namespace app\admin\controller;


use app\api\controller\Base;
use app\api\model\bill;
use app\api\model\footGame;

use app\api\model\orderDetail;
use app\api\service\OrderFactory;
use Illuminate\Bus\Batch;
use Illuminate\Database\QueryException;
use support\Request;

use Webman\Config;
use app\api\model\Order as OrderModel;
use support\Db;
use function DI\create;

class Order extends Base
{



    public function orderList(Request $request)
    {
        $state = $request->input("state",-1);
        $type = $request->input("type","");
        $time = $request->input("range","");
        $order_no = $request->input("order_no","");
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = \app\api\model\Order::with("orderDetails")->with("user");
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("order_time",$time);
        }
        if($state != -2){
            $model = $model->where("state",$state);
        }
        if($type){
            $model = $model->where("type",$type);
        }

        if($order_no){
            $model = $model->where("order_no",$order_no);
        }

        $data = $model->orderBy("order_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        \app\admin\model\Order::setOrderData($data);


        return $this->success($data);
    }

    public function cancel(Request $request){
        $id = $request->input("id",0);
        $data = \app\api\model\Order::find($id);
        if(!$data){
            return $this->error("","不存在此订单");
        }
        if($data->state != -1){
            return $this->error("","状态错误");
        }
        $data->state = 4;
        $data->cancel_time= time();
        $data->save();
        //退钱
        $model = \app\api\model\user::where("uid",$data->uid)->first();
        $model->now_money = $model->now_money+$data->amount;
        $model->save();
        bill::addBill($data->uid,$id,1,"撤销订单","now_money","cancel_order",$data->amount,$model->now_money,"撤销订单");
        //todo
        return $this->success($data);
    }

    public function printTick(Request $request){
        $id = $request->input("id",0);
        $data = \app\api\model\Order::find($id);
        if(!$data){
            return $this->error("","不存在此订单");
        }
        if($data->state != -1){
            return $this->error("","状态错误");
        }

        //更新订单赔率
        //todo
        $data_ = orderDetail::where("order_id",$id)->get()->toArray();

        if($data->type == "foot" || $data->type == "basket"){
            foreach ($data_ as $k=>&$v){
                $bets = $v["bet_content"];
                foreach ($bets as $k1=>&$v1){
                    $order_factory = new OrderFactory($data->type);
                    $pl = $order_factory->createOrderServiceImpl()->getOrderPl($v1["met"],$v1["game_id"]);
                    if (!$pl){
                        return $this->error("","获取不到赔率,无法出票,请联系技术人员");
                    }
                    $v1["pl"] = $pl;
                    $data_[$k]["bet_content"][$k1]["pl"] = $pl;
                }
               $detail = orderDetail::find($v["id"]);
               $detail->bet_content = serialize($data_[$k]["bet_content"]);
               $detail->save();
            }
        }
        $data->state = 0;
        $data->tick_time = time();
        $data->save();
        //给上级返佣
        //todo
        $user = \app\api\model\user::find($data->uid);
        if($user->pid){
            $p_user = \app\api\model\user::find($user->pid);
            if ($p_user){
                if($p_user->is_seller == 1){
                    if($p_user->yj_rate >0){
                        $back_money = round($data->amount*$p_user->yj_rate*0.01,2);
                        $p_user->award_amount = $p_user->award_amount + $back_money;
                        $p_user->save();
                        bill::addBill($p_user->uid,$id,1,"下单返佣","award_amount","order_back_money",$back_money, $p_user->award_amount,"下单返佣");
                    }
                }
            }
        }



        return $this->success($data);
    }

    public function sendAward(Request $request){
        $ids = $request->input("ids","");
        if(!$ids){
            return $this->error("","无效订单");
        }
        $ids = explode(",",$ids);
        Db::beginTransaction();
        try {
            //更新状态
            $model = \app\api\model\Order::whereIn("id",$ids);
            $model->update(["state"=>3,"send_award_time"=>time()]);
            //加钱
            $data = $model->get()->toArray();
            foreach ($data as $k=>$v){
                $u_model = \app\api\model\user::where("uid",$v["uid"])->lockForUpdate()->first();
                $u_model->award_amount  = $u_model->award_amount + $v["award_money"];
                $u_model->save();
                if($v["pid"] != 0){
                    //给上级返佣
                    $p_order = \app\admin\model\Order::find($v["pid"]);
                    $p_user = \app\api\model\user::where("uid",$p_order->uid)->lockForUpdate()->first();
                    $back_money = ($v["award_money"]/0.92)*0.04;
                    $p_user->award_amount = $p_user->award_amount + round($back_money,2);
                    $p_user->save();
                    bill::addBill($p_order->uid,$v["pid"],1,"中奖返佣","award_amount","win_prize_yj",$back_money,$p_user->award_amount,"中奖返佣");
                }
                bill::addBill($v["uid"],$v["id"],1,"中奖","award_amount","win_prize",$v["award_money"],$u_model->award_amount,"中奖".$v["award_money"]."元");
            }
            //财务明细
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return $this->error("",$e->getMessage());
        }

        return $this->success("");

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
