<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace app\middleware;

use app\api\controller\Order;
use app\api\controller\User;
use app\api\model\userCard;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

/**
 * Class StaticFile
 * @package app\middleware
 */
class RealNameCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {

        $arr = [Order::class=>["doOrder","doFlowOrder"],User::class=>["recharge","editAliCode","editBankCard","doWithdraw"]];

        if(isset($arr[$request->controller])){
            if (in_array($request->action,$arr[$request->controller])){
                $card = userCard::where("uid",getUser($request)->userid)->first();
                if(!$card){

                    return json(["code"=>503,"msg"=>'未实名',"data"=>""]);
                }
                if(!$card->id_card){
                    return json(["code"=>503,"msg"=>'未实名',"data"=>""]);
                }
            }
        }

        $response = $next($request);

        return $response;
    }
}
