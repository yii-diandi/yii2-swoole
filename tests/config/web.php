<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-19 20:27:34
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-20 04:24:28
 */
 


use diandi\swoole\web\ErrorHandler;
use diandi\swoole\web\Request;
use diandi\swoole\web\Response;
use yii\caching\FileCache;

$db = require(__DIR__ . '/../../../common/config/db.php');

return [
    'id' => 'swooleService',
    'name' => '店滴Swoole',
    'basePath' => dirname(__DIR__),
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'swooleService\controllers',
    'taskNamespace' => 'swooleService\tasks',
    'aliases' => [
        '@swooleService' => dirname(__DIR__),
    ],
    'components' => [
        'response' => [
            'class' => Response::class,
            'format' => Response::FORMAT_JSON
        ],
        'request' => [
            'class' => Request::class,
            'cookieValidationKey' => '123456'
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ]
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'class' => ErrorHandler::class
        ],
        'db' => $db,
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 2
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
    ],
    'params' => require __DIR__ . '/params.php'
];