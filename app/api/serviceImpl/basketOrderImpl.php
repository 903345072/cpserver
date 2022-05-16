<?php
namespace app\api\serviceImpl;


 use app\api\model\basketGame;
 use app\api\model\footGame;
 use app\api\model\Order;
 use support\Db;


 class basketOrderImpl extends commOrderImpl{

     public function getOrderStopTime($data)
     {
         foreach ($data as $k=>$v){
             foreach ($v as $k1=>$v1){
                 $check_ids[] = $v1["game_id"];
             }
         }
         $stop_time = array_column(basketGame::whereIn("id",$check_ids)->get()->toArray(),"stop_time");
         rsort($stop_time);
       return $stop_time[0];
     }

     public function getOrderPl($met,$game_id){
         $model = basketGame::find($game_id);
         $met_arr = explode("-",$met);
         $arr = "";
         if($met_arr[0] == 1){
             $arr = $model->mnl_odds;
         }
         if($met_arr[0] == 2){
             $arr = $model->hdc_odds;
         }
         if($met_arr[0] == 3){
             $arr = $model->hilo_odds;
         }
         if($met_arr[0] == 4){
             $arr = $model->wnm_lose.",".$model->wnm_win;
         }
         if(!$arr){
             return false;
         }

         $arr= explode(",",$arr);
         return isset($arr[$met_arr[1]-1])?$arr[$met_arr[1]-1]:false;
     }

     public function holdOrder($data,$type)
     {
         parent::holdOrder($data,$type); // TODO: Change the autogenerated stub

     }

     public function checkRight($met, $game_result,$p_goal,$name,$game_id)
     {





         $qcbf = $game_result["qcbf"];
         $met = explode("-",$met);
         $pre_no = $met[0];
         $suffix_no = $met[1];
         switch ($pre_no){
             case "1";
                 $r = explode(":",$qcbf);
                 $r = $r[1]-$r[0];//分差
                 if($r>0){
                     $ret_id = 2;
                 }else{
                     $ret_id = 1;
                 }
                 if($ret_id == $suffix_no){
                     return true;
                 }
                 break;
             case "2";
                 $r = explode(":",$qcbf);
                 $z = $r[1];
                 $k = $r[0];
                 if($p_goal<0){  //主队给客队让分
                     $r_ = $z-($k+abs($p_goal)) ;
                 }else if($p_goal>0){
                     //客队让球
                     $r_ = ($z+abs($p_goal))-$k;
                 }
                 if($r_>0){
                     $ret_id = 2;
                 }else{
                     $ret_id =1;
                 }
                 if ($ret_id == $suffix_no){
                     return true;
                 }
                 break;
             case "4";
                 $ar = explode(":",$qcbf);
                 $sfc = $ar[1]-$ar[0];
                 if($sfc>0){
                     if($sfc>=1 && $sfc<=5){
                         $ret_id = 7;
                     }
                     if($sfc>=6 && $sfc<=10){
                         $ret_id = 8;
                     }
                     if($sfc>=11 && $sfc<=15){
                         $ret_id = 9;
                     }
                     if($sfc>=16 && $sfc<=20){
                         $ret_id = 10;
                     }
                     if($sfc>=21 && $sfc<=25){
                         $ret_id = 11;
                     }
                     if($sfc>=26){
                         $ret_id = 12;
                     }
                 }else{
                         $sfc = abs($sfc);
                         if($sfc>=1 && $sfc<=5){
                             $ret_id = 1;
                         }
                         if($sfc>=6 && $sfc<=10){
                             $ret_id = 2;
                         }
                         if($sfc>=11 && $sfc<=15){
                             $ret_id = 3;
                         }
                         if($sfc>=16 && $sfc<=20){
                             $ret_id = 4;
                         }
                         if($sfc>=21 && $sfc<=25){
                             $ret_id = 5;
                         }
                         if($sfc>=26){
                             $ret_id = 6;
                         }
                 }
                 if ($ret_id == $suffix_no){
                     return true;
                 }
                 break;
             case "3":
                 $dxf = Db::table("eb_basketball_mix_odds")->where(["id"=>$game_id])->first();
                 $dxf = $dxf->p_goal;
                 $dxf = explode(",",$dxf);
                 $dxf = $dxf[3];
                 $ar = explode(":",$qcbf);
                 $total = $ar[0]+$ar[1];

                 if($total>=$dxf){
                     $ret_id = 1;
                 }else{
                     $ret_id = 2;
                 }

                 if($suffix_no==$ret_id){
                     return true;
                 }
                 break;

         }
         return false;
     }

 }
