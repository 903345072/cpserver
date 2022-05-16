<?php
namespace app\aspect;

use app\api\model\userCard;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

class mustRealAspect extends AbstractAspect{

    public $classes = [
        \app\api\controller\Order::class . '::doOrder',
        \app\api\controller\Order::class . '::doFlowOrder',
        \app\api\controller\User::class . '::recharge',
        \app\api\controller\User::class . '::editAliCode',
        \app\api\controller\User::class . '::editBankCard',
        \app\api\controller\User::class . '::doWithdraw',
    ];
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {

print_r(123);
        $card = userCard::where("uid",getUser(request())->userid)->first();
        if(!$card){
            return json(["code"=>503,"msg"=>'未实名',"data"=>""]);
        }
        if(!$card->id_card){
            return json(["code"=>503,"msg"=>'未实名',"data"=>""]);
        }
        return $proceedingJoinPoint->process();
        // TODO: Implement process() method.
    }
}