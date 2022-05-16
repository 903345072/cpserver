<?php

namespace app\api\model;

use Carbon\Traits\Date;
use support\Model;
use Webman\Config;


class bill extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_user_bill';

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
    protected $appends = ["time"];
    public function getTimeAttribute(){
        return date("Y-m-d H:i:s",$this->attributes["add_time"]);
    }


    public static function addBill($uid,$link_id,$pm,$title,$category,$type,$number,$balance,$mark){
        $model = new self();
        $model->uid = $uid;
        $model->link_id = $link_id;
        $model->pm = $pm;
        $model->title = $title;
        $model->category = $category;
        $model->type = $type;
        $model->number = $number;
        $model->balance = $balance;
        $model->mark = $mark;
        $model->add_time = time();
        $model->save();
    }


    public function user(){
        return $this->hasOne("app\api\model\user","uid","uid");
    }


}