<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-08-23 09:44:31
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-23 09:46:11
 */

namespace diandi\swoole\server;
 
class UdpServer extends BaseServer
{
     /**
     * @var int SOCKET类型-udp
     */
    public $sockType = SWOOLE_SOCK_UDP;
}

?>