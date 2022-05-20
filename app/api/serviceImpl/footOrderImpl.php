<?php
namespace app\api\serviceImpl;


 use app\api\model\bill;
 use app\api\model\footGame;
 use app\api\model\Order;
 use app\api\model\orderDetail;
 use app\api\model\user;
 use support\Db;

 class footOrderImpl extends commOrderImpl{

     public function getOrderStopTime($data)
     {
         foreach ($data as $k=>$v){
             foreach ($v as $k1=>$v1){
                 $check_ids[] = $v1["game_id"];
             }
         }
         $stop_time = array_column(footGame::whereIn("id",$check_ids)->get()->toArray(),"stop_time");
         ksort($stop_time);
       return $stop_time[0];
     }

     public function getOrderPl($met,$game_id){
         $model = footGame::find($game_id);
         $met_arr = explode("-",$met);
         $arr = "";
         if($met_arr[0] == 1){
             $arr = $model->had_odds;
         }
         if($met_arr[0] == 2){
             $arr = $model->hhad_odds;
         }
         if($met_arr[0] == 3){
             $arr = $model->crs_win.",".$model->crs_draw.','.$model->crs_lose;
         }
         if($met_arr[0] == 4){
             $arr = $model->ttg_odds;
         }
         if($met_arr[0] == 5){
             $arr = $model->hafu_odds;
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



         $final = $game_result["final"];
         $bcbf = $game_result["half"];
         $qcjq = explode(":",$final);
         $bcbf = explode(":",$bcbf);
         $fs_hin = $qcjq[0]; //主队全场进球
         $fs_ain = $qcjq[1]; //客队全场进球
         $hts_hin = $bcbf[0]; //主队半场进球
         $hts_ain = $bcbf[1]; //主队半场进球
         $met = explode("-",$met);
         $pre_no = $met[0];
         $suffix_no = $met[1];
         switch ($pre_no){
             case "1";
                 $r = $fs_hin-$fs_ain;
                 if($r>0){
                     $ret_id = 1;
                 }elseif ($r == 0){
                     $ret_id = 2;
                 }else{
                     $ret_id =3;
                 }
                 if($ret_id == $suffix_no){
                     return true;
                 }
             break;
             case "2";
                 if($p_goal<0){  //主队给客队让球
                     $r = $fs_hin-($fs_ain+abs($p_goal));
                 }else if($p_goal>0){
                     //客队给主队让球
                     $r = ($fs_hin+abs($p_goal))-$fs_ain;
                 }
                 if($r>0){
                     $ret_id = 1;
                 }elseif ($r == 0){
                     $ret_id = 2;
                 }else{
                     $ret_id =3;
                 }
                 if($ret_id == $suffix_no){
                     return true;
                 }
                 break;
             case "3";
                 $bifen = $name;
                 if($bifen == $final){  //比分正确
                     return true;
                 }else{
                    if($bifen == "胜其他"){
                        $r = $fs_hin-$fs_ain;
                        if($r>0 && !in_array($final,["1:0","2:0","3:0","4:0","5:0","2:1","3:1","4:1","5:1","3:2","4:2","5:2"])){
                            return true;
                        }
                    }
                    if($bifen == "平其他"){
                        $r = $fs_hin-$fs_ain;
                        if($r==0 && !in_array($final,["0:0","1:1","2:2","3:3"])){
                            return true;
                        }
                    }
                     if($bifen == "负其他"){
                         $r = $fs_hin-$fs_ain;
                         if($r<0 && !in_array($final,["0:1","0:2","0:3","0:4","0:5","1:2","1:3","1:4","1:5","2:3","2:4","2:5"])){
                             return true;
                         }
                     }
                 }
                 break;
             case "4":
                 $r = explode(":",$final);
                 $r = array_sum($r);
                 $total = (int)$name;
                 if($r == $total){ //总进球相等
                     return true;
                 }else{
                     if($total >= 7 && $r>=7){
                         return true;
                     }
                 }
                 break;
              case "5":
                  if($hts_hin-$hts_ain>0){
                      $ban_sf = "胜";
                  }elseif ($hts_hin-$hts_ain<0){
                      $ban_sf = "负";
                  }else{
                      $ban_sf = "平";
                  }

                  if($fs_hin-$fs_ain>0){
                      $quan_sf = "胜";
                  }elseif ($fs_hin-$fs_ain<0){
                      $quan_sf = "负";
                  }else{
                      $quan_sf = "平";
                  }
                  $r = $ban_sf.$quan_sf;

                  $sf =  $name;
                  if($r == $sf){
                     return true;
                  }
              break;
         }
         return false;
     }


 }
