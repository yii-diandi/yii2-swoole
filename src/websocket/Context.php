<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-09-14 15:03:38
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-09-14 15:05:07
 */

namespace diandi\swoole\websocket;

use diandi\swoole\coroutine\Context as CoroutineContext;

class Context extends CoroutineContext
{
    /**
     * 请求数据共享区.
     */
    const COROUTINE_DATA = 'websocket.data';

    /**
     * 当前请求 container.
     */
    const COROUTINE_CONTAINER = 'container';

    /**
     * 当前请求 Application.
     */
    const COROUTINE_APP = 'app';
}
