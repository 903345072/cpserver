<?php
namespace app\api\model;
use support\Model;

class userCard extends  Model{
    protected $table = 'eb_user_card';

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
}