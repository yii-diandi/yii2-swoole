<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-08-27 15:04:10
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-27 15:05:41
 */

namespace diandi\swoole\coroutine;



class CoroutineHelp
{
    /**
     * Create child coroutine in case to use parent`s context
     * @param \Closure $callback
     * @return mixed
     */
    public static function createChild(\Closure $callback)
    {
        $puid = Context::getcoroutine();
        return \Swoole\Coroutine::create(function () use ($puid,$callback){
            Context::markParent($puid);
            \Swoole\Coroutine::call_user_func($callback);
        });
    }
}