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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use support\Request;
use support\Response;
use support\Translation;
use support\Container;
use support\view\Raw;
use support\view\Blade;
use support\view\ThinkPHP;
use support\view\Twig;
use Workerman\Worker;
use Webman\App;
use Webman\Config;
use Webman\Route;

// Phar support.
if (is_phar()) {
    define('BASE_PATH', dirname(__DIR__));
} else {
    define('BASE_PATH', realpath(__DIR__ . '/../'));
}
define('WEBMAN_VERSION', '1.2.5');


/**
 * @param $return_phar
 * @return false|string
 */
function base_path($return_phar = true)
{
    static $real_path = '';
    if (!$real_path) {
        $real_path = is_phar() ? dirname(Phar::running(false)) : BASE_PATH;
    }
    return $return_phar ? BASE_PATH : $real_path;
}

function is_weekend(){
    $str =date('Y-m-d h:i:s', time());
    if((date('w',strtotime($str))==6) || (date('w',strtotime($str)) == 0)){

        return true;
    }else{

        return false;
    }
}

 function is_today($str){
    $c = date('Y-m-d');
     $b = substr($str,0,10);
    if($b == $c){
        return true;
    }
    return false;
}

/**
 * @return string
 */
function app_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'app';
}

/**
 * @return string
 */
function public_path()
{
    static $path = '';
    if (!$path) {
        $path = get_realpath(config('app.public_path', BASE_PATH . DIRECTORY_SEPARATOR . 'public'));
    }
    return $path;
}

/**
 * @return string
 */
function config_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'config';
}

/**
 * Phar support.
 * Compatible with the 'realpath' function in the phar file.
 *
 * @return string
 */
function runtime_path()
{
    static $path = '';
    if (!$path) {
        $path = get_realpath(config('app.runtime_path', BASE_PATH . DIRECTORY_SEPARATOR . 'runtime'));
    }
    return $path;
}

/**
 * @param int $status
 * @param array $headers
 * @param string $body
 * @return Response
 */
function response($body = '', $status = 200, $headers = array())
{
    return new Response($status, $headers, $body);
}

/**
 * @param $data
 * @param int $options
 * @return Response
 */
function json($data, $options = JSON_UNESCAPED_UNICODE)
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode($data, $options));
}

/**
 * @param $xml
 * @return Response
 */
function xml($xml)
{
    if ($xml instanceof SimpleXMLElement) {
        $xml = $xml->asXML();
    }
    return new Response(200, ['Content-Type' => 'text/xml'], $xml);
}

/**
 * @param $data
 * @param string $callback_name
 * @return Response
 */
function jsonp($data, $callback_name = 'callback')
{
    if (!is_scalar($data) && null !== $data) {
        $data = json_encode($data);
    }
    return new Response(200, [], "$callback_name($data)");
}

/**
 * @param $location
 * @param int $status
 * @param array $headers
 * @return Response
 */
function redirect($location, $status = 302, $headers = [])
{
    $response = new Response($status, ['Location' => $location]);
    if (!empty($headers)) {
        $response->withHeaders($headers);
    }
    return $response;
}

/**
 * @param $template
 * @param array $vars
 * @param null $app
 * @return Response
 */
function view($template, $vars = [], $app = null)
{
    static $handler;
    if (null === $handler) {
        $handler = config('view.handler');
    }
    return new Response(200, [], $handler::render($template, $vars, $app));
}

/**
 * @param $template
 * @param array $vars
 * @param null $app
 * @return Response
 */
function raw_view($template, $vars = [], $app = null)
{
    return new Response(200, [], Raw::render($template, $vars, $app));
}

/**
 * @param $template
 * @param array $vars
 * @param null $app
 * @return Response
 */
function blade_view($template, $vars = [], $app = null)
{
    return new Response(200, [], Blade::render($template, $vars, $app));
}

/**
 * @param $template
 * @param array $vars
 * @param null $app
 * @return Response
 */
function think_view($template, $vars = [], $app = null)
{
    return new Response(200, [], ThinkPHP::render($template, $vars, $app));
}

/**
 * @param $template
 * @param array $vars
 * @param null $app
 * @return Response
 */
function twig_view($template, $vars = [], $app = null)
{
    return new Response(200, [], Twig::render($template, $vars, $app));
}

/**
 * @return Request
 */
function request()
{
    return App::request();
}

/**
 * @param $key
 * @param null $default
 * @return mixed
 */
function config($key = null, $default = null)
{
    return Config::get($key, $default);
}

/**
 * @param $name
 * @param array $parameters
 * @return string
 */
function route($name, $parameters = [])
{
    $route = Route::getByName($name);
    if (!$route) {
        return '';
    }
    return $route->url($parameters);
}

/**
 * @param null $key
 * @param null $default
 * @return mixed
 */
function session($key = null, $default = null)
{
    $session = request()->session();
    if (null === $key) {
        return $session;
    }
    if (\is_array($key)) {
        $session->put($key);
        return null;
    }
    return $session->get($key, $default);
}

/**
 * @param null|string $id
 * @param array $parameters
 * @param string|null $domain
 * @param string|null $locale
 * @return string
 */
function trans(string $id, array $parameters = [], string $domain = null, string $locale = null)
{
    $res = Translation::trans($id, $parameters, $domain, $locale);
    return $res === '' ? $id : $res;
}

/**
 * @param null|string $locale
 * @return string
 */
function locale(string $locale = null)
{
    if (!$locale) {
        return Translation::getLocale();
    }
    Translation::setLocale($locale);
}

/**
 * Copy dir.
 * @param $source
 * @param $dest
 * @param bool $overwrite
 * @return void
 */
function copy_dir($source, $dest, $overwrite = false)
{
    if (is_dir($source)) {
        if (!is_dir($dest)) {
            mkdir($dest);
        }
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                copy_dir("$source/$file", "$dest/$file");
            }
        }
    } else if (file_exists($source) && ($overwrite || !file_exists($dest))) {
        copy($source, $dest);
    }
}

/**
 * Remove dir.
 * @param $dir
 * @return bool
 */
function remove_dir($dir)
{
    if (is_link($dir) || is_file($dir)) {
        return unlink($dir);
    }
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file") && !is_link($dir)) ? remove_dir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

/**
 * @param $worker
 * @param $class
 */
function worker_bind($worker, $class)
{
    $callback_map = [
        'onConnect',
        'onMessage',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerStop',
        'onWebSocketConnect'
    ];
    foreach ($callback_map as $name) {
        if (method_exists($class, $name)) {
            $worker->$name = [$class, $name];
        }
    }
    if (method_exists($class, 'onWorkerStart')) {
        call_user_func([$class, 'onWorkerStart'], $worker);
    }
}

/**
 * @param $process_name
 * @param $config
 * @return void
 */
function worker_start($process_name, $config)
{
    $worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);
    $property_map = [
        'count',
        'user',
        'group',
        'reloadable',
        'reusePort',
        'transport',
        'protocol',
    ];
    $worker->name = $process_name;
    foreach ($property_map as $property) {
        if (isset($config[$property])) {
            $worker->$property = $config[$property];
        }
    }

    $worker->onWorkerStart = function ($worker) use ($config) {
        require_once base_path() . '/support/bootstrap.php';

        foreach ($config['services'] ?? [] as $server) {
            if (!class_exists($server['handler'])) {
                echo "process error: class {$server['handler']} not exists\r\n";
                continue;
            }
            $listen = new Worker($server['listen'] ?? null, $server['context'] ?? []);
            if (isset($server['listen'])) {
                echo "listen: {$server['listen']}\n";
            }
            $instance = Container::make($server['handler'], $server['constructor'] ?? []);
            worker_bind($listen, $instance);
            $listen->listen();
        }

        if (isset($config['handler'])) {
            if (!class_exists($config['handler'])) {
                echo "process error: class {$config['handler']} not exists\r\n";
                return;
            }

            $instance = Container::make($config['handler'], $config['constructor'] ?? []);
            worker_bind($worker, $instance);
        }

    };
}

/**
 * Phar support.
 * Compatible with the 'realpath' function in the phar file.
 *
 * @param string $file_path
 * @return string
 */
function get_realpath(string $file_path): string
{
    if (is_phar()) {
        return $file_path;
    } else {
        return realpath($file_path);
    }
}

/**
 * @return bool
 */
function is_phar()
{
    return class_exists(\Phar::class, false) && Phar::running();
}

/**
 * @return int
 */
function cpu_count()
{
    // Windows does not support the number of processes setting.
    if (\DIRECTORY_SEPARATOR === '\\') {
        return 1;
    }
    if (strtolower(PHP_OS) === 'darwin') {
        $count = shell_exec('sysctl -n machdep.cpu.core_count');
    } else {
        $count = shell_exec('nproc');
    }
    $count = (int)$count > 0 ? (int)$count : 4;
    return $count;
}

function getStopTime($time,$game_time,$type="foot"){

    $forward_time = Config::get("gameTime")["forward_time"];
    if(is_weekend()){
        $limit_time = $game_time[1];
    }else{
        $limit_time = $game_time[0];
    }

    if($type == "foot" || $type == "basket"){
        //???00-11???(????????????)??????????????????????????????
        if(0<=Date("H",strtotime($time)) && Date("H",strtotime($time)) < date("H",strtotime($limit_time[0]))){
            $stop_time = Date("Y-m-d H:i:s",strtotime(Date("Y-m-d",strtotime($time))." ".$limit_time[1])-86400-$forward_time);
        }else if (strtotime($time) > strtotime(date("Y-m-d",strtotime($time))." ".$limit_time[1])){ //?????????????????????????????????????????????????????????
            $stop_time = Date("Y-m-d H:i:s", strtotime(Date("Y-m-d", strtotime($time)). " " .$limit_time[1]) - $forward_time);
        }else{
            $stop_time = Date("Y-m-d H:i:s",strtotime($time)-$forward_time);
        }
        if(strtotime($time) < time() || strtotime($stop_time) < time()){
            $stop_time = "ex";
        }
        return $stop_time;
    }

}



 function array_unique_2DArr($arr=array()){
    if(empty($arr) || !is_array($arr)){
        return array();
    }
    /*******???????????????????????????????????????  start ???????????????????????????*******/
    //???????????????????????????????????????uniqueId???????????????????????????????????????????????????????????????uniqueId??????uniqueId???????????????
    $hasUniqueId = false;
    foreach($arr as $val){
        if(array_key_exists('uniqueId', $val)){
            $hasUniqueId = true;
            break;
        }
    }
    //??????$arr?????????????????????uniqueId???????????????????????????
    if($hasUniqueId){
        foreach($arr as $_k=>$_val){
            if(!array_key_exists('uniqueId', $_val)){
                //???$_val?????????unique,??????????????????????????????uniqueId???????????????????????????????????????
                $_val_keys = array_keys($_val);
                $_val_vals = array_values($_val);
                array_unshift($_val_keys, 'uniqueId');
                array_unshift($_val_vals, '0_0');
                $arr[$_k] = array_combine($_val_keys, $_val_vals);
            }
        }
    }
    /********???????????????????????????????????????  end********/
    foreach($arr[0] as $k => $v){
        $arr_inner_key[]= $k;   //????????????????????????????????????????????????????????????????????????
    }
    foreach ($arr as $k => $v){
        $v =join("^",$v);   //?????? ???implode()?????? ??????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????????
        $temp[$k] =$v;      //?????????????????????
    }
    $temp =array_unique($temp);    //?????????????????????????????????
    foreach ($temp as $k => $v){
        $a = explode("^",$v);   //??????????????????
        $arr_after[$k]= array_combine($arr_inner_key,$a);  //?????????????????????????????????
    }
    return $arr_after;
}
