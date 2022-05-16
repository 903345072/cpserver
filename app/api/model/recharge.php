<?php

namespace app\api\model;

use Carbon\Traits\Date;
use support\Model;
use Webman\Config;


class recharge extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_user_recharge';

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
    protected $appends = ["add_time"];

    public function getAddTimeAttribute(){
        return date("Y-m-d H:i:s",$this->attributes["add_time"]);
    }

    public function user(){
        return $this->hasOne("app\api\model\user","uid","uid");
    }

}