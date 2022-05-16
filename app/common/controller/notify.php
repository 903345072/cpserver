<?php
namespace app\common\controller;

use app\api\controller\Base;
use app\api\model\bill;
use app\api\model\recharge;
use app\api\model\user;
use app\api\service\smsService;
use Firebase\JWT\JWT;
use support\Db;
use support\Request;
use Webman\Config;
use DI\Annotation\Inject;
class notify extends Base{


    public function ysfnotify(Request $request){
//        $data = json_decode(file_get_contents('php://input'), true);
        $data = $request->all();
        $result =  $data["status"];
        $order_id = $data["order_id"];
        if($result==1) {

            $userCharge = recharge::where(['order_id'=>$order_id])->first();
            if (!empty($userCharge)) {
                //充值状态：1待付款，2成功，-1失败
                if ($userCharge->paid == 0) {
                    //找到这个用户
                    $user = User::where(['uid'=>$userCharge['uid']])->first();
                    //给用户加钱
                    $add_price = $userCharge->price;
                    $add_price = $userCharge->price*1.02;
                    $user->now_money += $add_price;
                    if ($user->save()) {
                        //更新充值状态---成功
                        $userCharge->paid = 1;
                        $userCharge->pay_time = time();
                    }
                }
                //更新充值记录表
                bill::addBill($userCharge->uid,$userCharge->id,1,"充值","recharge","now_money",$userCharge->price,$user->now_money,"充值");
                $userCharge->save();
                return "SUCCESS";       //请不要修改或删除
            }else{
               return 'error';
            }
        }else{
            return 'error';

        }
    }

    public function jbmnotify(Request $request){


        $data = $request->all();

        $order_id =$data["agent_bill_id"];
        if($data["result"]==1) {
            $userCharge = recharge::where(['order_id'=>$order_id])->first();
            if (!empty($userCharge)) {
                //充值状态：1待付款，2成功，-1失败
                if ($userCharge->paid == 0) {
                    //找到这个用户
                    $user = User::where(['uid'=>$userCharge['uid']])->first();
                    //给用户加钱
                    $add_price = $userCharge->price;
                    $add_price = $userCharge->price*1.02;
                    $user->now_money += $add_price;
                    if ($user->save()) {
                        //更新充值状态---成功
                        $userCharge->paid = 1;
                        $userCharge->pay_time = time();
                    }
                    //更新充值记录表
                    bill::addBill($userCharge->uid,$userCharge->id,1,"充值","recharge","now_money",$userCharge->price,$user->now_money,"充值");
                    $userCharge->save();
                    return "SUCCESS";       //请不要修改或删除
                }
            }else{
                return 'aaa';
            }
        }else{
            return 'bbb';
        }
    }

}