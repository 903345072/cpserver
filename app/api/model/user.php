<?php

namespace app\api\model;

use Carbon\Traits\Date;
use support\Model;
use Webman\Config;
use Carbon\Carbon;

class user extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_user';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'uid';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $appends = ["fives","sum_amount","order_count","parent_user","bind_time","children_count","seven_target","total_money"];

    public function getFivesAttribute(){

        $data = isset($this->attributes["five_grade"])?unserialize($this->attributes["five_grade"]):[];
        if(!$data){
            $data = [];
        }
        return $data;
    }

    public function getBindTimeAttribute(){
        return isset($this->attributes["add_time"])?date("Y-m-d H:i:s",$this->attributes["add_time"]):"";
    }

    public function getTotalMoneyAttribute(){
        return round($this->attributes["now_money"]+$this->attributes["award_amount"],2);
    }

    public function getParentUserAttribute(){
        return $this->puser()->select("nickname")->value("nickname");
    }

    public function getSevenTargetAttribute(){
        $this_week = [time()-60*60*24*7, time()];
        $data = $this->orders()->select(["state","id","order_time"])->whereIn("state",[1,2,3])->whereBetween('order_time', $this_week)->get()->toArray();
        $total = count($data);
        $target = 0;
        foreach ($data as $k=>$v){
            if($v["state"] == 2 || $v["state"] == 3){
                $target++;
            }
        }
        return $total."中".$target;
    }

    public function puser(){
        return $this->hasOne(self::class,"uid","pid");
    }

    public function cardInfo(){
        return $this->hasOne(userCard::class,"uid","uid");
    }

    public function childrenUser(){
        return $this->hasMany(self::class,"pid","uid");
    }

    public function getChildrenCountAttribute(){
        return $this->childrenUser()->count();
    }



    public function orders(){
        return $this->hasMany('app\api\model\Order',"uid","uid")->orderBy("order_time","desc");
    }

    public function printedOrders(){
        $time = time()-24*60*60*15;
        return $this->hasMany('app\api\model\Order',"uid","uid")->whereIn("state",[0,1,2,3])->where("order_time",">",$time)->orderBy("order_time","desc");
    }

    public function getSumAmountAttribute(){
        return $this->orders()->where("state","!=",4)->sum("amount");
    }

    public function getOrderCountAttribute(){
        return $this->orders()->where("state","!=",4)->count();
    }

}