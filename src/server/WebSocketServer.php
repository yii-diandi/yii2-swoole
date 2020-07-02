<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2017/12/9
 * Time: 上午11:49
 */

namespace diandi\swoole\server;

use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class WebSocketServer
 * @package diandi\swoole\server
 * @deprecated please use swoole native web sockect
 */
class WebSocketServer extends HttpServer
{
    protected $serverType = 'websocket';

    public function onOpen(Server $server,$worker_id)
    {
        if($this->bootstrap){
            $this->bootstrap->onOpen($server,$worker_id);
        }
    }

    public function onMessage(Server $ws,Frame $frame){
        if($this->bootstrap){
            $this->bootstrap->onMessage($ws,$frame);
        }
    }

    public function onClose(Server $ws, $fd) {
        if($this->bootstrap){
            $this->bootstrap->onClose($ws,$fd);
        }
    }
}