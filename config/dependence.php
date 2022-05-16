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

use Psr\Container\ContainerInterface;
use app\api\service\smsService;
use app\api\serviceImpl\smsBao;
return [
    smsService::class => function(ContainerInterface $container) {
        return $container->make(smsBao::class);
    },
    \app\api\service\GatherService::class=>function(ContainerInterface $container){
        return $container->make(\app\api\serviceImpl\FeijingGather::class);
    }
];