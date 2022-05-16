<?php
namespace process;
use app\api\model\Order;
use app\api\service\GatherService;
use support\Db;
use Workerman\Crontab\Crontab;

class CronTask{

    /**
     * @Inject
     * @var GatherService
     */
     private $gatherService;

    public function onWorkerStart()
    {


        //采集足球
        new Crontab('*/3 * * * *', function(){
            $this->gatherService->gatherFootGame();
        });

        //更新足球
        new Crontab('*/3 * * * *', function(){
            $this->gatherService->updateFootGame();
        });

        //采集篮球
        new Crontab('*/3 * * * *', function(){
            $this->gatherService->gatherBasketGame();
        });

        //更新篮球
        new Crontab('*/3 * * * *', function(){
            $this->gatherService->updateBaketGame();
        });

        //平仓足球
        new Crontab('*/3 * * * *', function(){
            $data = Order::with("orderDetails")->where("state",0)->where("type","foot")->limit(300)->get()->toArray();
            $factory = new \app\api\service\OrderFactory("basket");
            $factory->createOrderServiceImpl()->holdOrder($data,"eb_football_mix_odds");
        });

        //平仓篮球
        new Crontab('*/3 * * * *', function(){
            $data = Order::with("orderDetails")->where("state",0)->where("type","foot")->limit(300)->get()->toArray();
            $factory = new \app\api\service\OrderFactory("foot");
            $factory->createOrderServiceImpl()->holdOrder($data,"eb_basketball_mix_odds");
        });
    }
}