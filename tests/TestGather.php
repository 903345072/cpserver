<?php


use app\api\model\Order;
use PHPUnit\Framework\TestCase;

class TestGather extends TestCase
{


    public function testHoldBasketOrder()
    {

        $data = Order::with("orderDetails")->where("state",0)->where("type","foot")->limit(300)->get()->toArray();
        $factory = new \app\api\service\OrderFactory("basket");
        $factory->createOrderServiceImpl()->holdOrder($data,"eb_basketball_mix_odds");
    }

    public function testHoldFootOrder()
    {


        $data = Order::with("orderDetails")->where("state",0)->where("type","foot")->limit(300)->get()->toArray();
        $factory = new \app\api\service\OrderFactory("foot");
        $factory->createOrderServiceImpl()->holdOrder($data,"eb_football_mix_odds");

    }

}