<?php
namespace app\api\service;

use app\api\model\Order;

interface OrderService{

    //创建订单
    public function create(Order $order);

    //撤销订单
    public function cancel();

    //查询单个订单
    public function findOneOrder();

    //查询列表订单
    public function findOrderList();

    //记录订单详情
    public function createDetail();

    //获取订单截止时间
    public function getOrderStopTime($data);

    public function getOrderPl($a,$b);

//    public function checkRight($met,$game_result,$p_goal,$name);

    public function holdOrder($data,$type);


}