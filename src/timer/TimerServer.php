<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-19 22:47:02
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-21 01:00:42
 */

namespace diandi\swoole\timer;

use Exception;
use Throwable;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use diandi\swoole\web\Application;
use Swoole\Timer as Server;
use Swoole\Server as BaseServer;


/**
 * 定时器
 * Class WebServer
 * @package app\timer
 */
class TimerServer extends BaseObject
{
  
    /**
     * @var string 监听主机
     */
    public $host = 'localhost';
    /**
     * @var int 监听端口
     */
    public $port = 9503;
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
        'worker_num'     => 1,   //必须设置为1
        'max_request'    => 10000,
        'open_eof_check' => true,        //打开EOF检测
        'package_eof'    => "\r\n\r\n", //设置EOF
        'open_eof_split' => true,        //启用EOF自动分包
        'dispatch_mode'  => 2,
        'debug_mode'     => 1 ,
        'daemonize'      => 1,
        'log_file'       => __DIR__ . '/Log/swoole.log'
    ];
    /**
     * @var array 应用配置
     */
    public $app = [];
    /**
     * @var Server swoole server实例
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

        if (!$this->server instanceof BaseServer) {
            $this->server = new BaseServer($this->host, $this->port, $this->mode, $this->sockType);
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
            'receive' => [$this, 'onReceive'],
            'tick' => [$this, 'onTick'],
            'after' => [$this, 'onAfter'],
            'clear' => [$this, 'onClear'],
            'clearAll' => [$this, 'onClearAll'],
            'info' => [$this, 'onInfo'],
            'list' => [$this, 'onList'],
            'stats' => [$this, 'onStats'],
            'set' => [$this, 'onSet']
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
     * @param \Swoole\Http\Server $server
     */
    public function onStart(Server $server)
    {
        printf("listen on %s:%d\n", $server->host, $server->port);
    }

    /**
     * 工作进程启动时实例化框架
     * @param \Swoole\Http\Server $server
     * @param int $workerId
     * @throws InvalidConfigException
     */
    public function onWorkerStart(BaseServer $server, $workerId)
    {
        new Application($this->app);
        Yii::$app->set('server', $server);
    }


    /**
     * 工作进程异常
     * @param \Swoole\Http\Server $server
     * @param $workerId
     * @param $workerPid
     * @param $exitCode
     * @param $signal
     */
    public function onWorkerError(Server $server, $workerId, $workerPid, $exitCode, $signal)
    {
        fprintf(STDERR, "worker error. id=%d pid=%d code=%d signal=%d\n", $workerId, $workerPid, $exitCode, $signal);
    }
    

    /**
     * 开启连接
     *
     * @param $server
     * @param $frame
     */
    public function onOpen($server, $frame)
    {
        echo "server: handshake success with fd{$frame->fd}\n";
        echo "server: {$frame->data}\n";

        // 验证token进行连接判断
    }

    public function onReceive(Server $server, $fd, $from_id, $data) {
        echo "Get Message From Client {$fd}:{$data}\n";
        // send a task to task worker.
        $param = array(
            'fd' => $fd
        );
        // start a task
        $this->server->task(json_encode($param));

        echo "Continue Handle Worker\n";
    }

    public function onClose(Server $server, $fd) {
        echo "Client Close.\n";
    }
    



    public function onTick(Server $server, ...$params)
    {
      
    }

    public function onAfter(Server $server, ...$params)
    {
      
    }

    public function onClear(Server $server,$timer_id)
    {
        return $this->server->clear($timer_id);
      
    }

    public function onClearAll()
    {
        return $this->server->clearAll();
      
    }

    
    public function onInfo(Server $server,$timer_id)
    {
        return $this->server->info($timer_id);
      
    }
    

    public function onList(Server $server)
    {
        return $this->server->list();
    }

    public function onStats(Server $server)
    {
        return $this->server->stats();
    }
    

    public function onSet(Server $server,$array)
    {
        return $this->server->set($array);
      
    }

   
}