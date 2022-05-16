<?php
namespace app\api\serviceImpl;
 use app\api\model\Order;
 use app\api\model\user;
 use support\Db;

 abstract class commOrderImpl implements \app\api\service\OrderService{

    public function create(Order $order)
    {


         return $order->save();
        // TODO: Implement create() method.
    }

    public function cancel()
    {
        // TODO: Implement cancel() method.
    }

    public function findOneOrder()
    {
        // TODO: Implement findOneOrder() method.
    }

    public function findOrderList()
    {
        // TODO: Implement findOrderList() method.
    }

     public function createDetail()
     {
         // TODO: Implement createDetail() method.
     }

     public function getOrderStopTime($data)
     {
         // TODO: Implement getOrderStopTime() method.
     }

     public function getOrderPl($a, $b)
     {
         // TODO: Implement getOrderPl() method.
     }

     public function holdOrder($data,$type)
     {
         // TODO: Implement holdOrder() method.

         foreach ($data as $k => $v){
             try {
                 $details = $v["order_details"];
                 $award_arr = [];
                 $is_finish_count = 0;
                 $t_count = 0;
                 foreach ($details as $k1=>$v1){
                     $bet_arr = $v1["bet_content"];//单注，如果此串中了即此订单就中奖
                     $t_count += count($bet_arr);
                     $flag = 0;
                     $zhu_pl= [];
                     foreach ($bet_arr as $k2=>&$v2){
                         $game = Db::table($type)->where("id",$v2["game_id"])->first();
                         if($game->m_status == "Played" && $game->result){
                             $is_finish_count ++;

                             //{"half":"3:0","final":"3:0","fs_hin":3,"fs_ain":0,"hts_hin":3,"hts_ain":0}
                             $game_result = json_decode($game->result,1);


                             $ret = $this->checkRight($v2["met"],$game_result,$v2["p_goal"],$v2["name"],$v2["game_id"]);
                             if($ret){
                                 $flag ++;
                                 $zhu_pl[] = $v2["pl"];
                                 Db::table("eb_order_detail")->where("id",$v1["id"])->update(["ret"=>1]);
                                 $v2["ret"] = 1;
                             }
                             $v2["qcbf"] = $type=="eb_football_mix_odds" || $type == "eb_basketball_mix_odds"?$game_result["final"]:$game_result["qcbf"];
                             $v2["bcbf"] = $type=="eb_football_mix_odds" || $type == "eb_basketball_bd_odds"?$game_result["half"]:$game_result["qcbf"];
                         }
                     }
                     Db::table("eb_order_detail")->where("id",$v1["id"])->update(["bet_content"=>serialize($bet_arr)]);
                     if($flag == count($bet_arr)){
                         $award_arr[] = ["pl"=>$zhu_pl,"num"=>$v1["num"]];
                     }
                 }


                 if($award_arr && $t_count == $is_finish_count){
                     $award_money = 0;
                     foreach ($award_arr as $k3=>$v3){
                         $m = 2*$v3["num"];
                         foreach ($v3["pl"] as $k4=>$v4){
                             $m*=$v4;
                         }
                         $award_money+=$m;
                     }
                     $order = Order::find($v["id"]);
                     $order->state = 2;
                     $order->award_money = $order->pid ==0?$award_money:$award_money*0.92 ;
                     $order->save();
                     $this->updateUserInfo($v["uid"],1);
                 }else if(!$award_arr && $t_count == $is_finish_count){
                     $order = Order::find($v["id"]);
                     $order->state = 1;
                     $order->save();
                     $this->updateUserInfo($v["uid"],0);
                 }
             }catch (\Exception $e){

             }


         }
     }

      abstract function checkRight($met, $game_result,$p_goal,$name,$game_id);
      private function updateUserInfo($uid,$flag){
          $user =  user::find($uid);
          $five_grade = unserialize($user->five_grade);
          if($flag == 1){
              $user->lianhong+=1;
              array_unshift($five_grade,1);
          }else{
              $user->lianhong = 0;
              array_unshift($five_grade,0);
          }
          if(count($five_grade)>5){
              $five_grade =  array_slice($five_grade,0,5);
          }

          $user->five_grade = serialize($five_grade);
          $this_week = [time()-60*60*24*7, time()];
          $seven_model =Order::where("uid",$uid)->whereIn("state",[1,2,3])->whereBetween("order_time",$this_week);
          $seven_order_amount = $seven_model->sum("amount");
          $seven_order_award_money =  $seven_model->sum("award_money");
          $profit_rate = round($seven_order_award_money*100/$seven_order_amount,2);
          $user->profit_rate = $profit_rate;
          $all_count = $seven_model->get()->toArray();
          $target_count = 0;
          foreach ($all_count as $k5=>$v5){
              if($v5["state"] == 2 || $v5["state"] == 3){
                  $target_count++;
              }
          }
          $win_rate = round($target_count*100/count($all_count),2);
          $user->win_rate = $win_rate;
          $user->save();

      }

 }
