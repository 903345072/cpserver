<?php
namespace app\api\model;
use support\Model;
class orderDetail extends Model{
    protected $table = 'eb_order_detail';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $id;
    protected $bet_content;
    protected $order_id;
    protected $num;
    protected $appends = ["bet_content"];

    public function getBetContentAttribute()
    {

        return unserialize($this->attributes["bet_content"]);

    }
}