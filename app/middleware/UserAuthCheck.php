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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

/**
 * Class StaticFile
 * @package app\middleware
 */
class UserAuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {

       $token = $request->header("bear_token","");

        $key = '123456'; //key要和签发的时候一样

        try {

            $decoded = JWT::decode($token,new Key($key, 'HS256')); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;
        } catch(\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            return json(["code"=>502,"msg"=>$e->getMessage(),"data"=>""]);

        }catch(\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            return json(["code"=>502,"msg"=>$e->getMessage(),"data"=>""]);
        }catch(\Firebase\JWT\ExpiredException $e) {  // token过期
            return json(["code"=>502,"msg"=>$e->getMessage(),"data"=>""]);
        }catch(\Exception $e) {  //其他错误
            return json(["code"=>502,"msg"=>$e->getMessage(),"data"=>""]);
        }
        $response = $next($request);

        return $response;
    }
}
