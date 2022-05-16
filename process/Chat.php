<?php
namespace process;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;
class Chat{

   public static $id_arr = [];
    //心跳检测
    public function onWorkerStart(Worker $worker)
    {
        Timer::add(10, function()use($worker){
            $time_now = time();
            foreach($worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastMessageTime > 15) {
                    echo '超时了';
                    $connection->close();
                }
            }

        });
    }

    public function onConnect(TcpConnection $connection)
    {

        echo "onConnect\n";
    }

    public function onWebSocketConnect(TcpConnection $connection, $http_buffer)
    {

        echo "onWebSocketConnect\n";
    }

    public function onMessage(TcpConnection $connection, $data)
    {


        $connection->lastMessageTime = time();
        $arr = json_decode($data,1);
        if($arr["msg"]["type"] != "ping"){
            $arr["msg"]["userinfo"]["uid"] = 1;
            $arr["msg"]["id"] = $arr["msg"]["id"] + 1;
            $arr["msg"]["content"] = ["text"=>"神采科技"];
            $connection->send(json_encode($arr));
        }else{

            $connection->send($data);
        }

    }

    public function onClose(TcpConnection $connection)
    {
        echo "onClose\n";
    }
}