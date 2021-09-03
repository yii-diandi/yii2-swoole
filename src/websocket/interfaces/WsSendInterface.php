<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-09-03 16:58:54
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-09-03 17:02:28
 */

namespace diandi\swoole\websocket\interfaces;

interface WsSendInterface
{
    public function send($server, $data, $to = null);

    public function sendDataByUser($server, $data);
}
