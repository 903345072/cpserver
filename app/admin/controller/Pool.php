<?php
namespace app\admin\controller;

use app\api\controller\Base;
use app\api\model\bill;
use app\api\model\extract;
use app\api\model\recharge;
use support\Request;
use Webman\Config;

class Pool extends Base{
    public function getTimeArea(Request $request){
        $start_time = strtotime(date('Y-m-d',time()));
        $end_time = $start_time + 24*60*60-1;
        $start_time = date("Y-m-d H:i:s",$start_time);
        $end_time = date("Y-m-d H:i:s",$end_time);
        return $this->success([$start_time,$end_time]);
    }
    public function index(Request $request){
        $time = $request->input("range","");
        $order_model = new \app\api\model\Order();
        $bill_model = new bill();
        $extract_model = new extract();
        $order_model = $order_model->with("user")->whereHas("user",function ($q){
            $q->where("is_moni",0);
        });
        $bill_model = $bill_model->with("user")->whereHas("user",function ($q){
            $q->where("is_moni",0);
        });
        $extract_model = $extract_model->with("user")->whereHas("user",function ($q){
            $q->where("is_moni",0);
        });
        $order_model_ = clone $order_model;
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];

            $order_model = $order_model->whereBetween("tick_time",$time);
            $order_model_ = $order_model_->whereBetween("send_award_time",$time);
            $bill_model =$bill_model->whereBetween("add_time",$time);
            $extract_model =$extract_model->whereBetween("add_time",$time);
        }

        $bill_model1 = clone $bill_model;
        $bill_model2 = clone $bill_model;

        $chupiao = $order_model->whereIn("state",[0,1,2,3])->sum("amount");
        $zhongjiang = $order_model_->where("state",3)->sum("amount");
        $recharge = $bill_model->where("type","recharge")->sum("number");
        $add = $bill_model1->where("type","system")->where("pm",1)->sum("number");
        $sub = $bill_model2->where("type","system")->where("pm",0)->sum("number");
        $extract = $extract_model->where("status",1)->sum("extract_price");
        return $this->success(compact("chupiao","zhongjiang","recharge","add","sub","extract"));
    }

    public function getRechargeList(Request $request){
        $pay_type = ["全部","支付宝","微信"];
        $time = $request->input("range","");
        $type = $request->input("type",0);
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = new recharge();
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("add_time",$time);
        }
        if($type){
            $model = $model->where("recharge_type",$pay_type[$type]);
        }
        $model = $model->where("paid",1);
        $all = $model->sum("price");
        $data = $model->with("user")->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(["all"=>$all,"data"=>$data,"pay_type"=>$pay_type]);
     }

    public function getWithdrawList(Request $request){
        $pay_type = ["已结算","已撤销"];
        $type = $request->input("type",0);
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = new extract();
        $model = $model->where("status",$type==0?1:-1);
        $all = $model->sum("extract_price");
        $data = $model->with("user")->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(["all"=>$all,"data"=>$data,"pay_type"=>$pay_type]);
    }

    public function getWithdrawApplyList(Request $request){

        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = new extract();
        $model = $model->where("status",0);
        $data = $model->with("user")->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success($data);
    }

    public function getShopInfo(Request $request){
        $name = Config::get("shop")["name"];
        $logo = Config::get("shop")["logo"];
        $user_count = \app\api\model\user::where("is_moni",0)->count();
        $shop_money1 = \app\api\model\user::where("is_moni",0)->sum("award_amount");
        $shop_money2 = \app\api\model\user::where("is_moni",0)->sum("now_money");
        $shop_money = $shop_money1+$shop_money2;
        return $this->success(compact("name","logo","user_count","shop_money"));
    }

    public function getSystemList(Request $request){
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $time = $request->input("range",1);
        $type = $request->input("type",0);
        $model = new bill();
        if($time){
            $time = explode(",",$time);
            $time = [strtotime($time[0]),strtotime($time[1])];
            $model = $model->whereBetween("add_time",$time);
        }
        $model = $model->with("user")->where("type","system")->where("pm",$type);
        $all = $model->sum("number");
        $data = $model->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(compact("all","data"));
    }
    public function refuseWithdraw(Request $request){
        $id = $request->input("id","");
        $mark = $request->input("mark","");
        $model = extract::find($id);
        if(!$model){
            return $this->error("","无记录");
        }
        $model->status = -1;
        $model->mark = $mark;
        $model->fail_time = time();
        $model->save();
        $u_model = \app\api\model\user::find($model->uid);
        $u_model->award_amount = $u_model->award_amount + $model->extract_price;

        $u_model->save();
        bill::addBill($model->uid,$model->id,1,"拒绝提现","award_amount","withdraw",$model->extract_price,$u_model->award_amount,"拒绝提现");
        return $this->success("","");
    }

    public function passWithdraw(Request $request){
        $id = $request->input("id","");
        $mark = $request->input("mark","通过提现");
        $model = extract::find($id);
        if(!$model){
            return $this->error("","无记录");
        }
        $model->status = 1;
        $model->mark = $mark;
        $model->save();
        $bill = bill::where("link_id",$id)->first();
        $bill->status = 1;
        $bill->title = "通过提现";
        $bill->save();
        return $this->success("","");
    }
}