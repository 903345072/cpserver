<?php

namespace app\api\controller;


use app\api\model\bill;
use app\api\model\extract;
use app\api\model\footGame;

use app\api\model\recharge;
use app\api\model\userCard;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;

use support\Request;
use support\Db;
use Webman\Config;
use Workerman\Protocols\Http;

class User extends Base
{
    public function userInfo(Request $request)
    {
        $data = getUser($request);

        $data = \app\api\model\user::with("cardInfo")->where("uid",$data->userid)->first()->toArray();
        $data["shop_name"] = \config("shop")["name"];
        $data["all_money"] = $data["now_money"] + $data["award_amount"];
        $url ="http://localhost:8080/pages/my/register?code=".$data["invite_code"];
        //$url = urlencode($url);
        $data["qrcode"] = "http://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=0&data=$url";
        $guanzhu = Db::table("eb_user_flow")->where("uid",$data["uid"])->count();
        $fans_count =Db::table("eb_user_flow")->where("flow_user_id",$data["uid"])->count();
        return $this->success(compact("data","guanzhu","fans_count"));
    }

    public function test(Request $request)
    {


        $name = footGame::getUnstartGames();
        return $this->success($name);

    }

    public function savePwd(Request $request){
        $data = getUser($request);
        $pwd= $request->input("pwd","");
        $pwd = md5($pwd);
        $model = \app\api\model\user::find($data->userid);
        $model->pwd = $pwd;
        $model->save();
        return $this->success("");
    }


    public function editAvatar(Request $request){
        $file = $request->file('upload');
        $id = $request->input('id',"");

        if ($file && $file->isValid()) {
            $tt = time().rand(1111,999999);
            $path = public_path()."/avatar/$tt".".".$file->getUploadExtension();
            $file->move($path);
            $model = \app\api\model\user::where("uid",$id)->first();
            $model->avatar = "/avatar/$tt".".".$file->getUploadExtension();
            $model->save();
            return $this->success($model);
        }
        return $this->error("","no");
    }

    public function RealNameAuthentication(Request $request){
        $name = $request->input("name");
        $card = $request->input("card");
        $url = "http://route.showapi.com";

        $showapi_appid="998128";//替换此值,你可以在这里找到 https://www.showapi.com/console#/myApp
        $showapi_sign="caacc68037854814bf087bcd0e3e8bbb";//替换此值,你可以在这里找到 https://www.showapi.com/console#/myApp
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $url,
            // You can set any number of default request options.
            'timeout'  => 3.0,
            'headers' => [
                "Content-type" => 'application/json'
            ]

        ]);
       $res = $client->post("1072-1?showapi_appid=$showapi_appid&showapi_sign=$showapi_sign",['form_params' => [
               'name' => $name,
               'idcard'=>$card
           ]]);
       $data = json_decode($res->getBody()->getContents(),1);
       if($data["showapi_res_body"]["code"] != 0){
          return $this->error("",$data["showapi_res_body"]["msg"]);
       }
       $birthday = $data["showapi_res_body"]["birthday"];
        $birthday = explode("-",$birthday);
        $age = getAgeByBirth($birthday[0],$birthday[1],$birthday[2]);
        if($age < 18){
            return $this->error("","未成年不得购买体彩!");
        }
        Db::table("eb_user_card")->insert(["id_card"=>$card,"real_name"=>$name,"uid"=>getUser($request)->userid]);
        return $this->success("");

    }

    public function getRealAuthentication(Request $request){
        $user = getUser($request);
        $data = Db::table("eb_user_card")->where(["uid"=>$user->userid])->first();
        return $this->success($data);
    }

    public function recharge(Request $request){
        $type = $request->input("type","");
        $price = $request->input("price","");
        $model = new recharge();

        $model->uid = getUser($request)->userid;
        $model->order_id = date('YmdHis', time()).rand(1000,9999).$model->uid;
        $model->price = $price;
        $model->recharge_type = $type;
        $model->add_time = time();
        $model->paid = 0;
        $model->save();

        if($type == "alipay1" || $type == "alipay2"){
            $data["external_id"] = $type=="alipay1"? "CT202204271532052675924535349886":"CT202202241336322023768146335977";
            $data["order_id"] = $model->order_id;
            $data["money"] = $price;
            $data["notice"] = "http://".$request->getLocalIp().":".$request->getLocalPort()."/common/notify/ysfnotify";

            $s_data = $data;
            ksort($s_data);
            $sign_str = implode("",$s_data);
            $key = $type=="alipay1"?20220427154357:20220224162139;
            $sign = md5($sign_str.$key); //计算签名值
            $data["sign"] = $sign;
            $client = new Client([
                'verify' =>false,
                'base_uri' => "https://h5pay.91shualian.com",
                'timeout'  => 3.0,
                'headers' => [
                    "Content-type" => 'application/x-www-form-urlencoded'
                ]
            ]);
            $res = $client->post("UnifiedApi/TradeApi/CreateOrder",['form_params' => $data]);
            $res = json_decode($res->getBody()->getContents(),1);
            $uri = $res["pay_url"];
            $uri = urlencode($uri);
            $url = "alipays://platformapi/startapp?appId=20000067&url=$uri";
        }else{

            $version = 1;
            $agent_id = "2135076";
            $agent_bill_id =$model->order_id;
            $agent_bill_time = date('YmdHis', time());
            $pay_type = 34;
            $pay_amt = $price;
            $notify_url = "http://".$request->getLocalIp().":".$request->getLocalPort()."/common/notify/ysfnotify";
            $user_ip = $request->getRealIp();
            $return_url = "http://www.baidu.com";
            $goods_name = "recharge";
            $goods_num = 1;
            $goods_note = "recharge";
            $remark = "recharge";
            $sign_key = "0C2CB491F599465C97587524"; //签名密钥，需要商户使用为自己的真实KEY

            $sign_str = '';
            $sign_str  = $sign_str . 'version=' . $version;
            $sign_str  = $sign_str . '&agent_id=' . $agent_id;
            $sign_str  = $sign_str . '&agent_bill_id=' . $agent_bill_id;
            $sign_str  = $sign_str . '&agent_bill_time=' . $agent_bill_time;
            $sign_str  = $sign_str . '&pay_type=' . $pay_type;
            $sign_str  = $sign_str . '&pay_amt=' . $pay_amt;
            $sign_str  = $sign_str .  '&notify_url=' . $notify_url;
            $sign_str  = $sign_str . '&return_url=' . $return_url;
            $sign_str  = $sign_str . '&user_ip=' . $user_ip;
            $sign_str = $sign_str . '&key=' . $sign_key;

            $sign = md5($sign_str); //计算签名值
            $url ="https://pay.heepay.com/Payment/Index.aspx?version={$version}&agent_id={$agent_id}&agent_bill_id={$agent_bill_id}&agent_bill_time={$agent_bill_time}&pay_type={$pay_type}&pay_amt={$pay_amt}&notify_url={$notify_url}&return_url={$return_url}&user_ip={$user_ip}&goods_name={$goods_name}&goods_num={$goods_num}&goods_note={$goods_note}&remark=$remark&is_phone=1&sign_type=MD5&sign={$sign}";
        }
        return $this->success($url);

    }
    public function editAliCode(Request $request){
        $ali_code = $request->input("ali_code");
        $card = userCard::where("uid",getUser($request)->userid)->first();
        $card->alipay_code = $ali_code;
        $card->save();
        return $this->success("");

    }

    public function editBankCard(Request $request){
        $bank_card = $request->input("bank_card");
        $bank_name = $request->input("bank_name");
        $card = userCard::where("uid",getUser($request)->userid)->first();
        $card->bank_card = $bank_card;
        $card->bank_name = $bank_name;
        $card->save();
        return $this->success("");

    }
    public function checkIsReal(Request $request){
        $card = userCard::where("uid",getUser($request)->userid)->first();
        if(!$card){
            return $this->success(["res"=>0]);
        }
        if(!$card->id_card){
            return $this->success(["res"=>0]);
        }
        return $this->success(["res"=>1,"card"=>$card]);
    }

    public function doWithdraw(Request $request){
       $price = $request->input("price","");
       if(!$price || $price<=0){
           return $this->error("","请输入正确金额");
       }
       $index = $request->input("type","");
       $arr = ["alipay","bank"];
       $type = $arr[$index];
       Db::beginTransaction();
        try {
            $user = \app\api\model\user::with("cardInfo")->where("uid",getUser($request)->userid)->lockForUpdate()->first();

            if($user->award_amount < $price){
                return $this->error("","余额不足");
            }
            $user->award_amount = $user->award_amount-$price;
            $user->save();
            $extract =new extract();
            $extract->uid = $user->uid;
            $extract->real_name = $user->cardInfo->real_name;
            $extract->extract_type = $type;
            $extract->bank_code = $user->cardInfo->bank_card?$user->cardInfo->bank_card:"无";
            $extract->bank_name = $user->cardInfo->bank_name?$user->cardInfo->bank_name:"无";
            $extract->alipay_code = $user->cardInfo->alipay_code?$user->cardInfo->alipay_code:"无";
            $extract->wechat = $user->cardInfo->wechat?$user->cardInfo->wechat:"无";
            $extract->balance = $price;
            $extract->extract_price = $price;
            $extract->add_time = time();
            $extract->status = 0;
            $extract->save();
            bill::addBill($user->uid,$extract->id,0,"提现","award_amount","withdraw",$price,$user->award_amount,"用户提现");
            Db::commit();
        }catch (\Exception $e){
            Db::rollBack();
            return $this->error("",$e->getMessage());
        }
        return $this->success("",'');
    }

    public function getWithdrawList(Request $request){
        $pay_type = ["已结算","已撤销"];
        $type = $request->input("type",0);
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        $model = new extract();
        $model = $model->where("status",$type==0?1:-1)->where("uid",getUser($request)->userid);
        $all = $model->sum("extract_price");
        $data = $model->with("user")->orderBy("add_time","desc")->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success(["all"=>$all,"data"=>$data,"pay_type"=>$pay_type]);
    }

    public function getBillList(Request $request){
        $uid = $request->input("uid","");
        if(!$uid){
            $uid = getUser($request)->userid;
        }
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

    public function registerList(Request $request)
    {
        $uid = $request->input("uid","");
        if(!$uid){
            $uid = getUser($request)->userid;
        }
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

    public function orderRecord(Request $request)
    {
        $uid = $request->input("uid","");
        if(!$uid){
            $uid = getUser($request)->userid;
        }
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
        \app\api\model\Order::setOrderData($data);
        return $this->success($data);
    }

    public function getUserList(Request $request){
        $name = $request->input("name");
        $model = \app\api\model\user::query();
        $page =  $request->input("pageNo",1);
        $pageSize =  $request->input("pageSize",10);
        if($name){
            $model->where("real_name",$name)->orWhere('real_name','like','%'.$name."%");
        }
        $data = $model->offset(($page-1)*$pageSize)->limit($pageSize)->get()->toArray();
        return $this->success($data);
    }
}
