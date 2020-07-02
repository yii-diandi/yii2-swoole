<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2018/1/15
 * Time: 下午5:27
 */

namespace diandi\swoole;

class BaseYii extends \yii\BaseYii
{
    /**
     * 由于Yii的静态化,需要另一个上下文对象来处理协程对象
     * @var \diandi\swoole\di\Context
     */
    public static $context;
    /**
     * @var \Swoole\Server
     */
    public static $swooleServer;
}