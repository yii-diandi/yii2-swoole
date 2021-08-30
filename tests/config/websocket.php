<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-19 20:27:15
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-20 04:14:02
 */
return [
    'host' => '0.0.0.0',
    'port' => 9502,
    'mode' => SWOOLE_PROCESS,
    'sockType' => SWOOLE_SOCK_TCP,
    'type'=>'ws',
    'app' => require __DIR__ . '/web.php',
    'options' => [
        'pid_file' => __DIR__ . '/../runtime/websocket.pid',
        'log_file' => __DIR__ . '/../runtime/websocket.log',
        'debug_mode'=> 1,
        'user'=>'www',
        'group'=>'www',
        // 4.0 新增选项
        'worker_num' => 2,
        'daemonize' => 0,
        'task_worker_num' => 10
    ]
];