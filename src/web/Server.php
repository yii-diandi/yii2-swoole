<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-19 22:47:02
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-22 17:22:04
 */

/**
 * @author xialeistudio
 * @date 2019-05-17
 */

namespace diandi\swoole\web;

use common\helpers\ResultHelper;
use diandi\swoole\coroutine\Context;
use Exception;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Web服务器
 * Class WebServer
 * @package app\servers
 */
class Server extends BaseObject
{
    /**
     * @var string 监听主机
     */
    public $host = 'localhost';
    /**
     * @var int 监听端口
     */
    public $port = 9501;
    /**
     * @var int 进程模型
     */
    public $mode = SWOOLE_PROCESS;
    /**
     * @var int SOCKET类型
     */
    public $sockType = SWOOLE_SOCK_TCP;
    /**
     * @var array 服务器选项
     */
    public $options = [
        'worker_num' => 2,
        'daemonize' => 0,
        'task_worker_num' => 2,
    ];
    /**
     * @var array 应用配置
     */
    public $app = [];
    /**
     * @var \Swoole\Http\Server swoole server实例
     */
    public $webServer;

    /**
     * 请求上下文
     * @var [type]
     * @date 2022-08-22
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public $context;

    public $process;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->app)) {
            throw new InvalidConfigException('The "app" property mus be set.');
        }

        if (!$this->webServer instanceof \Swoole\Http\Server) {
            $this->webServer = new \Swoole\Http\Server($this->host, $this->port, $this->mode, $this->sockType);
            $this->webServer->set($this->options);
        }

        foreach ($this->events() as $event => $callback) {
            if (method_exists($this, 'on' . $event)) {
                $this->webServer->on($event, $callback);
            }
        }
    }

    /**
     * 事件监听
     * @return array
     */
    public function events()
    {
        return [
            'start' => [$this, 'onStart'],
            'workerStart' => [$this, 'onWorkerStart'],
            'workerStop' => [$this, 'onWorkerStop'],
            'workerError' => [$this, 'onWorkerError'],
            'request' => [$this, 'onRequest'],
            'task' => [$this, 'onTask'],
            'finish' => [$this, 'onFinish'],
        ];
    }

    /**
     * 服务运行入口
     * @param array $config swoole配置文件
     * @param callable $func 启动回调
     */
    public function run()
    {
        global $argv;
        if (!isset($argv[0], $argv[1])) {
            print_r("invalid run params,see help,run like:php http-server.php start|stop|reload" . PHP_EOL);
            return;
        }
        $command = $argv[1];

        $pidFile = $this->options['pid_file'];

        $masterPid = file_exists($pidFile) ? file_get_contents($pidFile) : null;
        if ($command == 'start') {
            if ($masterPid > 0 and posix_kill($masterPid, 0)) {
                print_r('Server is already running. Please stop it first.' . PHP_EOL);
                exit;
            }
            return $this->webServer->start();
        } elseif ($command == 'stop') {
            if (!empty($masterPid)) {
                posix_kill($masterPid, SIGTERM);
                if (PHP_OS == "Darwin") {
                    //mac下.发送信号量无法触发shutdown.
                    unlink($pidFile);
                }
            } else {
                print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGTERM.' . PHP_EOL);
            }
            exit;
        } elseif ($command == 'reload') {
            if (!empty($masterPid)) {
                posix_kill($masterPid, SIGUSR1); // reload all worker
                //                posix_kill($masterPid, SIGUSR2); // reload all task
            } else {
                print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGUSR1.' . PHP_EOL);
            }
            exit;
        }
    }

    /**
     * 启动服务器
     * @return bool
     */
    public function start()
    {
        return $this->webServer->start();
    }

    /**
     * master启动
     * @param \Swoole\Http\Server $server
     */
    public function onStart(\Swoole\Http\Server $webServer)
    {
        @swoole_set_process_name("ddicms-webServer");
        printf("listen on %s:%d\n", $this->host, $this->port);
    }

    /**
     * 工作进程启动时实例化框架
     * @param \Swoole\Http\Server $server
     * @param int $workerId
     * @throws InvalidConfigException
     */
    public function onWorkerStart(\Swoole\Http\Server $webServer, $workerId)
    {
        @swoole_set_process_name("ddicms-webServer");
        new Application($this->app);
        Yii::$app->set('webServer', $webServer);
    }

    public function onWorkerStop(\Swoole\Http\Server $webServer, $workerId)
    {
        echo 'onWorkerStop';
    }

    /**
     * 工作进程异常
     * @param \Swoole\Http\Server $server
     * @param $workerId
     * @param $workerPid
     * @param $exitCode
     * @param $signal
     */
    public function onWorkerError(\Swoole\Http\Server $webServer, $workerId, $workerPid, $exitCode, $signal)
    {
        fprintf(STDERR, "worker error. id=%d pid=%d code=%d signal=%d\n", $workerId, $workerPid, $exitCode, $signal);
    }

    /**
     * 处理请求
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        global $_GPC;
        Yii::$app->request->setRequest($request);
        Yii::$app->response->setResponse($response);

        // list($controller, $action) = explode('/', trim($request->server['request_uri'], '/'));

        // print_r($controller, $action);

        // //根据 $controller, $action 映射到不同的控制器类和方法
        // (new $controller)->$action($request, $response);
        

        // var_dump($response->isWritable()); // false
        // $response->setStatusCode(403);

        if(Yii::$app->response->checkAccess($response)){
            Yii::$app->run();
            Yii::$app->response->clear();
        }
    }

    /**
     * 分发任务
     * @param \Swoole\Http\Server $server
     * @param $taskId
     * @param $workerId
     * @param $data
     * @return mixed
     */
    public function onTask(\Swoole\Http\Server $webServer, $taskId, $workerId, $data)
    {
        try {
            $handler = $data[0];
            $params = $data[1] ?? [];
            list($class, $action) = $handler;

            $obj = new $class();
            return call_user_func_array([$obj, $action], $params);
        } catch (Throwable $e) {
            Yii::$app->errorHandler->handleException($e);
            return 1;
        }
    }

    public function onFinish(\Swoole\Http\Server $webServer, $taskId, $data)
    {
        echo "Task#$taskId finished, data_len=" . strlen($data) . PHP_EOL;
    }
}
