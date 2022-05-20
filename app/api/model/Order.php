<?php

namespace app\api\model;

use Carbon\Traits\Date;
use support\Model;
use support\Request;
use Webman\Config;


class Order extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_order';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $guarded = ["uid","chuan","chuans"];

    protected $id;


    protected $chuan;


    protected $order_no;
    protected $order_time;
    protected $mode;
    protected $state;
    protected $uid;
    protected $type;
    protected $amount;
    protected $bei;
    protected $num;
    protected $pid;

    protected $award_money;
    protected $jia_jiang;
    protected $plan_title;
    protected $plan_desc;
    protected $start_money;
    protected $win_yj;
    protected $game_info;
    protected $order_pic;
    protected $min_pl;
    protected $yuji_jj;
    protected $stop_time;
    protected $tick_time;
    protected $appends = ['en_type',"order_time","en_state",'tick_time',"stop_time","chuan","send_award_time","cancel_time","en_mode","is_checked","stime","grouptime_h","grouptime_m","grouptime_s"];

    public static function setOrderData(&$data){
        foreach ($data as $k=>$v){
            if($v["type"] == "foot" || $v["type"] == "basket" || $v["type"] == "bd"){
                foreach ($v["order_details"] as $k1=>$v1){
                    foreach ($v1["bet_content"] as $k2=>$v2){
                        $uid = \request()?getUser(\request())->userid:"-1";
                        if($uid == $v["uid"] || $v["is_open"] == 1){

                            $data[$k]['order_detail'][$v2["game_id"]][] = $v2;
                            if(isset($data[$k]['order_detail'][$v2["game_id"]])){
                                if(is_array($data[$k]['order_detail'][$v2["game_id"]])){
                                    $data[$k]['order_detail'][$v2["game_id"]] =array_unique_2DArr($data[$k]['order_detail'][$v2["game_id"]]);
                                }
                            }
                        }else{
                            if($v["type"] == "foot"){
                                $game = footGame::where("id",$v2["game_id"])->first();
                            }
                            if($v["type"] == "basket"){
                                $game = basketGame::where("id",$v2["game_id"])->first();
                            }
                            if(strtotime($game->dtime) < time()){
                                $data[$k]['order_detail'][$v2["game_id"]][] = $v2;
                                if(isset($data[$k]['order_detail'][$v2["game_id"]])){
                                    if(is_array($data[$k]['order_detail'][$v2["game_id"]])){
                                        $data[$k]['order_detail'][$v2["game_id"]] =array_unique_2DArr($data[$k]['order_detail'][$v2["game_id"]]);
                                    }
                                }
                            }
                        }


                    }
                    unset($data[$k]['order_details']);
                }

                if (isset($data[$k]['order_detail'])){
                    $arr =  $data[$k]['order_detail'];
                    foreach ($arr as $k3=>$v3){
                        $data[$k]['order_detail_'][] = $v3;
                    }
                    unset($data[$k]['order_detail']);
                }else{
                    $data[$k]['order_detail_'] = [];
                }

            }

        }
    }

    public function orderDetails(){
        return $this->hasMany('app\api\model\orderDetail',"order_id","id");
    }
    public function user(){
        return $this->hasOne('app\api\model\user',"uid","uid");
    }



    public function getEnTypeAttribute()
    {
        $arr = \config("gameType");
        if (isset($this->attributes['type']) && !empty($this->attributes['type'])){
            return $arr[$this->attributes['type']];
        }else{
            return "";
        }

    }



    public function getStimeAttribute()
    {
        if(isset($this->attributes['stop_time']) && !empty($this->attributes['stop_time'])){
            $time = $this->attributes['stop_time']-time();
            return $time;
        }else{
            return 0;
        }

    }

    public function getGrouptimeHAttribute()
    {


        if(isset($this->attributes['stop_time']) && !empty($this->attributes['stop_time'])){
            $time = $this->attributes['stop_time']-time();
            $a = floor(($time/3600)%24)+floor($time/86400)*24;
            return $a<10?"0".$a:$a;
        }else{
            return "0";
        }

    }

    public function getGrouptimeMAttribute()
    {


        if(isset($this->attributes['stop_time']) && !empty($this->attributes['stop_time'])){
            $time = $this->attributes['stop_time']-time();
            $a = floor(($time/60)%60);
            return $a<10?"0".$a:$a;
        }else{
            return "0";
        }
    }

    public function getGrouptimeSAttribute()
    {
        if(isset($this->attributes['stop_time']) && !empty($this->attributes['stop_time'])){
            $time = $this->attributes['stop_time']-time();
            $a = floor(($time)%60);
            return $a<10?"0".$a:$a;
        }else{
            return "0";
        }

    }


    public function getIsCheckedAttribute()
    {

        return false;

    }

    public function getEnModeAttribute()
    {
        if (isset($this->attributes['mode']) && !empty($this->attributes['mode'])){
            $mode = $this->attributes['mode'];
            if($mode == 1){
                return "普通";
            }
            if($mode == 2){
                return "发单";
            }
            if($mode == 3){
                return "跟单";
            }
            if($mode == 4){
                return "优化";
            }
        }else{
            return "";
        }
    }

    public function getEnStateAttribute()
    {
        if($this->attributes["state"] == -1){
            return "出票中";
        }
        if($this->attributes["state"] == 0){
            return "未开奖";
        }
        if($this->attributes["state"] == 1){
            return "未中奖";
        }
        if($this->attributes["state"] == 2){
            return "待派奖";
        }
        if($this->attributes["state"] == 3){
            return "已派奖";
        }
        if($this->attributes["state"] == 4){
            return "已撤销";
        }


    }

    public function getOrderTimeAttribute()
    {

        return date("Y-m-d H:i:s",$this->attributes["order_time"]);

    }

    public function getTickTimeAttribute()
    {

        if (isset($this->attributes["tick_time"]) && !empty($this->attributes["tick_time"])){
            return date("Y-m-d H:i:s",$this->attributes["tick_time"]);
        }else{
            return "暂未出票";
        }

    }

    public function getSendAwardTimeAttribute()
    {

        if (isset($this->attributes["send_award_time"]) && !empty($this->attributes["send_award_time"])){
            return $this->attributes["send_award_time"]? date("Y-m-d H:i:s",$this->attributes["send_award_time"]):"未派奖";
        }else{
            return "未派奖";
        }

    }
    public function getCancelTimeAttribute()
    {

        if (isset($this->attributes["cancel_time"]) && !empty($this->attributes["cancel_time"])){
            return $this->attributes["cancel_time"]? date("Y-m-d H:i:s",$this->attributes["cancel_time"]):"无";
        }else{
            return "无";
        }

    }
    public function getStopTimeAttribute()
    {

        if(isset($this->attributes["stop_time"]) && !empty($this->attributes["stop_time"])){
            return date("Y-m-d H:i:s",$this->attributes["stop_time"]);
        }else{
            return "暂无";
        }

    }

    public function getChuanAttribute()
    {

        if (isset($this->attributes["chuan"]) && !empty($this->attributes["chuan"])){
            $chuan = $this->attributes["chuan"];
            $chuan = explode(",",$chuan);

            foreach ($chuan as $k=>&$v){
                if($v != '单关'){
                    $v = $v."串1";
                }
            }
            $chuan = implode(",",$chuan);
            return $chuan;
        }else{
            return "";
        }

    }

    public function getChuansAttribute()
    {
        return $this->attributes["chuan"];
    }


}