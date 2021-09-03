<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-09-03 11:51:19
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-09-03 15:26:17
 */

namespace diandi\swoole\websocket\events;


use yii\base\Component;
use yii\base\Event;

class MessageEvent extends Event
{
    public $message;
}