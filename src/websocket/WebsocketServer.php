<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2017/12/9
 * Time: 上午11:49
 */

namespace diandi\swoole\websocket;

use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Exception;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Class WebSocketServer
 * @package diandi\swoole\server
 * @deprecated please use swoole native web sockect
 */
class WebsocketServer extends BaseObject
{
      /**
     * @var string 监听主机
     */
    public $host = 'localhost';
    /**
     * @var int 监听端口
     */
    public $port = 9502;
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
        'task_worker_num' => 2
    ];
    /**
     * @var array 应用配置
     */
    public $app = [];
    /**
     * @var \Swoole\WebSocket\Server swoole server实例
     */
    public $server;

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

        if (!$this->server instanceof \Swoole\WebSocket\Server) {
            $this->server = new \Swoole\WebSocket\Server($this->host, $this->port, $this->mode, $this->sockType);
            $this->server->set($this->options);
        }

        foreach ($this->events() as $event => $callback) {
            $this->server->on($event, $callback);
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
            'workerError' => [$this, 'onWorkerError'],
            'task' => [$this, 'onTask'],
            'finish' => [$this, 'onFinish'],
            'open' => [$this, 'onOpen'],
            'message' => [$this, 'onMessage'],
            'close' => [$this, 'onClose'],
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
        if(!isset($argv[0],$argv[1])){
            print_r("invalid run params,see help,run like:php http-server.php start|stop|reload".PHP_EOL);
            return;
        }
        $command = $argv[1];
        
        
        $pidFile = $this->options['pid_file'];
        
        
        $masterPid     = file_exists($pidFile) ? file_get_contents($pidFile) : null;
        if ($command == 'start'){
            if ($masterPid > 0 and posix_kill($masterPid,0)) {
                print_r('Server is already running. Please stop it first.'.PHP_EOL);
                exit;
            }
            return $this->server->start();
        }elseif($command == 'stop'){
            if(!empty($masterPid)){
                posix_kill($masterPid,SIGTERM);
                if(PHP_OS=="Darwin"){
                    //mac下.发送信号量无法触发shutdown.
                    unlink($pidFile);
                }
            }else{
                print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGTERM.'.PHP_EOL);
            }
            exit;
        }elseif($command == 'reload'){
            if (!empty($masterPid)) {
                posix_kill($masterPid, SIGUSR1); // reload all worker
//                posix_kill($masterPid, SIGUSR2); // reload all task
            } else {
                print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGUSR1.'.PHP_EOL);
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
        return $this->server->start();
    }

    /**
     * master启动
     * @param \Swoole\WebSocket\Server $server
     */
    public function onStart(Server $server)
    {
        printf("listen on %s:%d\n", $server->host, $server->port);
    }

    /**
     * 工作进程启动时实例化框架
     * @param \Swoole\WebSocket\Server $server
     * @param int $workerId
     * @throws InvalidConfigException
     */
    public function onWorkerStart(Server $server, $workerId)
    {
        new Application($this->app);
        Yii::$app->set('server', $server);
    }

      /**
     * 分发任务
     * @param \Swoole\Http\Server $server
     * @param $taskId
     * @param $workerId
     * @param $data
     * @return mixed
     */
    public function onTask(\Swoole\Http\Server $server, $taskId, $workerId, $data)
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

    public function onFinish(\Swoole\Http\Server $server, $taskId, $data)
    {
        echo "Task#$taskId finished, data_len=" . strlen($data) . PHP_EOL;
    }


    /**
     * 工作进程异常
     * @param \Swoole\WebSocket\Server $server
     * @param $workerId
     * @param $workerPid
     * @param $exitCode
     * @param $signal
     */
    public function onWorkerError(Server $server, $workerId, $workerPid, $exitCode, $signal)
    {
        fprintf(STDERR, "worker error. id=%d pid=%d code=%d signal=%d\n", $workerId, $workerPid, $exitCode, $signal);
    }



    public function onOpen(Server $server,$worker_id)
    {
        if($this->server){
            $this->server->onOpen($server,$worker_id);
        }
    }

    public function onMessage(Server $ws,Frame $frame){
        if($this->server){
            $this->server->onMessage($ws,$frame);
        }
    }

    public function onClose(Server $ws, $fd) {
        if($this->server){
            $this->server->onClose($ws,$fd);
        }
    }
}