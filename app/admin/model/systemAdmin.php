<?php
namespace app\admin\model;
use support\Model;
class systemAdmin extends Model{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_system_admin';

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