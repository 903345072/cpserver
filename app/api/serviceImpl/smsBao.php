<?php
namespace app\api\serviceImpl;
use app\api\service\smsService;

class smsBao implements smsService{

    public function send($phone, $code)
    {
        // TODO: Implement send() method.
        $statusStr = array(
            "0" => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        $smsapi = "http://api.smsbao.com/";
        $user = "chuxin99"; //短信平台帐号
        $pass = md5("chuxin99"); //短信平台密码
        $content="【老九门】您的验证码为{$code}，在1分钟内有效";//要发送的短信内容
        $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
        $result =file_get_contents($sendurl);
        if ($result == 0){
            return true;
        }else{
            throw new \Exception($statusStr[$result]);
        }
    }
}