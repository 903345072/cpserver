<?php
namespace app\common\controller;

use app\api\controller\Base;
use app\api\model\user;
use app\api\service\smsService;
use Firebase\JWT\JWT;
use support\Db;
use support\Request;
use Webman\Config;
use DI\Annotation\Inject;
class index extends Base{
    /**
     * @Inject
     * @var smsService
     */
    private $smsService;

    public function shopInfo(Request $request){
        $logo = Config::get("shop")["logo"];
        $name = Config::get("shop")["name"];
        $phone = Config::get("shop")["phone"];
        $wechat = Config::get("shop")["wechat"];
        $gonggao = Db::table("eb_system_config")->where("menu_name","gonggao")->value("value");
        return $this->success(compact("logo","name","phone","wechat","gonggao"));
    }

    public function Login(Request $request){
        $account = $request->input("account","");
        $password = MD5($request->input("password",""));

        $model = \app\admin\model\systemAdmin::where("account",$account)->first();
        if($model){

            if($model->pwd != $password){
                return $this->error("","密码错误");
            }
        }else{
            return $this->error("","用户不存在");

        }
        $key = '344'; //key
        $time = time(); //当前时间
        $token = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'aud' => 'http://www.helloweba.net', //接收该JWT的一方，可选
            'iat' => $time, //签发时间
            'nbf' => $time , //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time+3600*24*14, //过期时间,这里设置2个小时
            'data' => [ //自定义信息，不要定义敏感信息
                'userid' => $model->id,
                'username' => $model->account
            ]
        ];
        $token = JWT::encode($token, $key,"HS256"); //输出Token

        return $this->success($token,"登录成功");
    }

    public function userLogin(Request $request){
        $account = $request->input("account","");
        $password = MD5($request->input("password",""));

        $model = user::where("account",$account)->first();
        if($model){

            if($model->pwd != $password){
                return $this->error("","密码错误");
            }
        }else{
            return $this->error("","用户不存在");

        }
        $key = '123456'; //key
        $time = time(); //当前时间
        $token = [
            'iss' => 'http://www.helloweba.net', //签发者 可选
            'aud' => 'http://www.helloweba.net', //接收该JWT的一方，可选
            'iat' => $time, //签发时间
            'nbf' => $time , //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time+3600*24*14, //过期时间,这里设置2个小时
            'data' => [ //自定义信息，不要定义敏感信息
                'userid' => $model->uid,
                'username' => $model->real_name
            ]
        ];
        $token = JWT::encode($token, $key,"HS256"); //输出Token
        return $this->success(["token"=>$token,'user'=>$model],"登录成功");
    }

    public function sendCode(Request $request){
        $phone = $request->input("phone","");
        $code = rand(1000,9999);
        Db::table("eb_sms")->insert(["phone"=>$phone,"code"=>$code,"time"=>time()]);
        $this->smsService->send($phone,$code);
        return $this->success('');

    }

    public function register(Request $request){
        $real_name = $request->input("real_name","");
        $account = $request->input("account","");
        $password = $request->input("password","");
        $repassword = $request->input("repassword","");
        $invite_code = $request->input("invite_code","");
        $code = $request->input("code","");
        $avatar = "/logo.jpg";
        if(!$real_name || !$account || !$password || !$invite_code || !$code){
            return $this->error('','请填写完整信息');
        }
        if($password != $repassword){
            return $this->error('','两次密码不一致');
        }

        $data = Db::table("eb_sms")->where(["phone"=>$account,"code"=>$code,"status"=>0])->get()->toArray();
        if(!$data){
            return $this->error('','验证码错误');
        }
        $p_user = user::where("invite_code",$invite_code)->first();
        if(!$p_user){
            return $this->error('','邀请码不存在');
        }
        if(user::where("account",$account)->first()){
            return $this->error('','手机号已存在');
        }
        $model = new user();
        $model->account = $account;
        $model->phone = $account;
        $model->pid = $p_user->id;
        $model->pwd = md5($password);
        $model->avatar = $avatar;
        $r = rand(11111,99999);
        while (user::where("invite_code",$r)->first()){
            $r = rand(11111,99999);
        }
        $model->invite_code = $r;
        $model->real_name = $real_name;
        $model->nickname = $real_name;
        $model->five_grade = serialize([]);
        $model->add_time = time();
        $model->user_type = 'h5';
        $model->save();
        Db::table("eb_sms")->where(["phone"=>$account,"code"=>$code])->update(["status"=>1]);
        return $this->success("");
    }
    public function verifyCode(Request $request){
        $code = $request->input("code","");
        if($code != $request->session()->get('captcha') || !$code){
            return $this->error('','验证码错误');
        }
        return $this->success('');
    }
}