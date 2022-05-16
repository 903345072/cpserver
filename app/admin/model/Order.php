<?php
namespace app\admin\model;
use app\api\model\basketGame;
use app\api\model\footGame;
use support\Model;
class Order extends \app\api\model\Order {
    public static function setOrderData(&$data){
        foreach ($data as $k=>$v){
            if($v["type"] == "foot" || $v["type"] == "basket" || $v["type"] == "bd"){
                foreach ($v["order_details"] as $k1=>$v1){
                    foreach ($v1["bet_content"] as $k2=>$v2){
                            $data[$k]['order_detail'][$v2["game_id"]][] = $v2;
                            if(isset($data[$k]['order_detail'][$v2["game_id"]])){
                                if(is_array($data[$k]['order_detail'][$v2["game_id"]])){
                                    $data[$k]['order_detail'][$v2["game_id"]] =array_unique_2DArr($data[$k]['order_detail'][$v2["game_id"]]);
                                }
                            }
                    }
                    unset($data[$k]['order_details']);
                }
                $arr =  $data[$k]['order_detail'];
                foreach ($arr as $k3=>$v3){
                    $data[$k]['order_detail_'][] = $v3;
                }
                unset($data[$k]['order_detail']);

            }

        }
    }
}