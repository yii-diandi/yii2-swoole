<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2017/2/24
 * Time: 下午5:26
 */

namespace yiiunit\extension\swoole\server;

use yiiunit\extension\swoole\TestCase;
use diandi\swoole\server\Server;


class ServerTest extends TestCase5
{
    function testAutoCreate(){
        $config = require __DIR__.'/../config/swoole.php';
        Server::autoCreate($config);
    }
}
