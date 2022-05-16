<?php
namespace app\api\service;
use ReflectionClass;
use ReflectionException;

class OrderFactory{
    public $namespace  = 'app\api\serviceImpl\\';
    public $type = "foot";
    public function __construct($type)
    {
        $this->type = $type;
    }

    public function createOrderServiceImpl() :OrderService{

        $className = $this->namespace . $this->type . 'OrderImpl';
        try {
            $class = new ReflectionClass($className);
            $obj = $class->newInstance();
        } catch (ReflectionException $Exception) {
            throw new \InvalidArgumentException('暂不支持的玩法');
        }
        return $obj;
    }
}