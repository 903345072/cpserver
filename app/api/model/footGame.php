<?php

namespace app\api\model;

use Carbon\Traits\Date;
use support\Model;
use Webman\Config;


class footGame extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_football_mix_odds';

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
    protected $dtime;
    protected $appends = ['stop_time','week','suffix_num','goal'];


    public function getStopTimeAttribute()
    {
        $game_time = Config::get("gameTime")["foot"];
        return getStopTime($this->attributes["dtime"],$game_time,"foot");

    }
    public function getWeekAttribute()
    {
        $week = ["一","二","三","四","五","六","日"];
        return $week[substr($this->attributes["num"],0,1)-1];
    }
    public function getSuffixNumAttribute()
    {
        return substr($this->attributes["num"],1);
    }
    public function getGoalAttribute()
    {
        return    explode(",",$this->attributes["p_goal"])[1]>0?"+".explode(",",$this->attributes["p_goal"])[1]:explode(",",$this->attributes["p_goal"])[1];

    }
    /**
     * @return mixed
     */
    public static function getUnstartGames(){

        $data = self::where("m_status","Fixture")->where("dtime",">",date("Y-m-d H:i:s",time()))->orderBy("num","asc")->get();
        $d = [];
        foreach ($data as $k=>$v){
            if($v["dtime"] != "ex"){
                $d[date("Y-m-d",strtotime($v["stop_time"]))][] = $v;
            }
        }
        ksort($d);
        return $d;
    }
}