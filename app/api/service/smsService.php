<?php
namespace app\api\service;
interface smsService{
   public function send($phone,$code);
}