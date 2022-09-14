<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-05-21 22:11:59
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-09-14 20:52:27
 */

namespace diandi\swoole;

use yii\BaseYii as YiiBaseYii;

class BaseYii extends YiiBaseYii
{
    /**
     * 上下文持久对象，这里存着每个请求协程 new 的 application.
     *
     * @var Context
     */
    public static $context;
}
