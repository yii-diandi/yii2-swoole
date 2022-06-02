<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-05-21 22:12:02
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-06-02 17:08:01
 */

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace diandi\swoole\events;

use yii\base\Event;

/**
 * Push Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class BaseServerEvent extends Event
{
    /**
     * @var int
     */
    public $fd;
    /**
     * @var mixed
     */
    public $reactorId;
    public $data;
    public $clientInfo;
    public $message;
    public $workerId;
    public $workerPid;
    public $exitCode;
    public $signal;
}
