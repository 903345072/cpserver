<?php
namespace app\api\controller;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use support\Cache;
use support\Db;
use support\Request;

class DaShen extends Base{

    public function getDashenList(Request $request){
        $user = \app\api\model\user::query()->select(["uid","avatar","is_dashen","lianhong","win_rate","profit_rate","real_name","now_money","award_amount"]);
        $tuijian = $user->where("is_dashen",1)->orderBy("dashen_order","desc")->limit(4)->get()->toArray();
        $lianhong =   $user->orderBy("lianhong","desc")->limit(4)->get()->toArray();
        $shenglv =   $user->orderBy("win_rate","desc")->limit(4)->get()->toArray();
        $yingli =   $user->orderBy("profit_rate","desc")->limit(4)->get()->toArray();
        return $this->success(compact('tuijian','lianhong','shenglv','yingli'));
    }

    public function getPageList(Request $request){

        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $type = $request->input("type",0);

        if($type == 0){
            $model = \app\api\model\Order::with("user")->where("mode",2)->where("state",0)->where("stop_time",">",time())->orderBy("flow_count","desc")->orderBy("order_time","desc");
        }

        if($type == 1){
            $model = \app\api\model\Order::with("user")->where("mode",2)->where("state",0)->where("stop_time",">",time())->orderBy("flow_amount","desc")->orderBy("order_time","desc");
        }

        if($type == 2){
            $guanzhu = Db::table("eb_user_flow")->where("uid",getUser($request)->userid)->get()->toArray();
            $guanzhu_ids = array_column($guanzhu,"flow_user_id");
            $model = \app\api\model\Order::with("user")->whereHas("user",function ($q)use($guanzhu_ids){
                $q->whereIn("uid",$guanzhu_ids);
            })->where("mode",2)->where("state",0)->where("stop_time",">",time())->orderBy("flow_amount","desc");
        }
        $data = $model->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(["data"=>$data]);
    }

    public function getOrderDetail(Request $request){
        $id = $request->input("id");
        $data = \app\api\model\Order::with("orderDetails")->with("user")->where("id",$id)->get()->toArray();

        $is_guanzhu = Db::table("eb_user_flow")->where("flow_user_id",$data[0]["uid"])->where("uid",getUser($request)->userid)->first();
        $data[0]["is_guanzhu"] = $is_guanzhu?1:0;
        \app\api\model\Order::setOrderData($data);
        return $this->success($data[0]);
    }

    public function getFlowRecord(Request $request){
        $id = $request->input("id");
        $data = \app\api\model\Order::with("user")->where("pid",$id)->limit(10)->get()->toArray();
        return $this->success($data);
    }

    public function guanzhu(Request $request){
        $id = $request->input("uid");
        if($id == getUser($request)->userid){
            return $this->error("","不能关注自己");
        }
        $data = Db::table("eb_user_flow")->where("uid",getUser($request)->userid)->where("flow_user_id",$id)->first();
        if($data){
            Db::table("eb_user_flow")->delete($data->id);
        }else{
            Db::table("eb_user_flow")->insert(["uid"=>getUser($request)->userid,"flow_user_id"=>$id]);
        }
        return $this->success("");
    }

    public function getHomePage(Request $request){
        $uid = $request->input("uid");
        $data = \app\api\model\user::with("printedOrders")->where("uid",$uid)->first()->toArray();
        $fans_count = Db::table("eb_user_flow")->where("flow_user_id",$uid)->count();
        $o_m = Db::table("eb_order")->where("uid",$uid);
        $c_om = clone $o_m;
        $send_count = $o_m->where("mode",2)->whereIn("state",[1,2,3])->count();
        $total_award = $c_om->sum("award_money");
        $data["fans_count"] = $fans_count;
        $data["send_count"] = $send_count;
        $data["total_award"] = $total_award;
        $is_guanzhu = Db::table("eb_user_flow")->where("flow_user_id",$data["uid"])->where("uid",getUser($request)->userid)->first();
        $data["is_guanzhu"] = $is_guanzhu?1:0;
        return $this->success($data);
    }
}
