<?php
/**
 * @author xialeistudio
 * @date 2019-05-17
 */
return [
    'host' => 'localhost',
    'port' => 9501,
    'mode' => SWOOLE_PROCESS,
    'sockType' => SWOOLE_SOCK_TCP,
    'app' => require __DIR__ . '/web.php',
    'options' => [
        'pid_file' => __DIR__ . '/../runtime/swoole.pid',
        'log_file' => __DIR__ . '/../runtime/swoole.log',
        'debug_mode'=> 1,
        'user'=>'www',
        'group'=>'www',
        // 4.0 新增选项
        'worker_num' => 2,
        'daemonize' => 0,
        'task_worker_num' => 2,
        'enable_coroutine' => COROUTINE_ENV
    ]
];