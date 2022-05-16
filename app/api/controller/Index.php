<?php

namespace app\api\controller;


use app\api\model\basketGame;
use app\api\model\footGame;

use support\Request;
use support\Db;
use Webman\Config;

class Index extends Base
{
    public function index(Request $request)
    {
        return response('hello webman1');
    }

    public function footGames(Request $request)
    {


        $name = footGame::getUnstartGames();
        return $this->success($name);

    }
    public function basketGames(Request $request)
    {
        $name = basketGame::getUnstartGames();
        return $this->success($name);
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
