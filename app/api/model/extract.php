<?php

namespace app\api\model;

use Carbon\Traits\Date;
use support\Model;
use Webman\Config;


class extract extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_user_extract';

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

    protected $appends = ["add_time","en_type","en_status","account"];
    public function getAddTimeAttribute(){
        return date("Y-m-d H:i:s",$this->attributes["add_time"]);
    }

    public function getEnStatusAttribute(){
        $type =$this->attributes["status"];
        if($type == -1){
            return "已拒绝";
        }
        if($type == 1){
            return "已结算";
        }


    }
    public function getAccountAttribute(){
        $type =$this->attributes["extract_type"];
        if($type == "bank"){
            return $this->attributes["bank_code"];
        }
        if($type == "alipay"){
            return $this->attributes["alipay_code"];
        }
        if($type == "wx"){
            return $this->attributes["wechat"];
        }

    }
    public function getEnTypeAttribute(){
        $type =$this->attributes["extract_type"];
        if($type == "bank"){
            return "银行卡提现";
        }
        if($type == "alipay"){
            return "支付宝提现";
        }
        if($type == "wx"){
            return "微信提现";
        }

    }
    public function user(){
        return $this->hasOne("app\api\model\user","uid","uid");
    }


}