<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:39
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-09-07 08:57:20
 */

namespace diandi\swoole\tcp\server;

use function Swoole\Coroutine\run;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server;
use Swoole\Coroutine\Server\Connection;
use Swoole\Process;
use yii\base\BaseObject;

/**
 * tcpf服务.
 *
 * Class TcpServer
 */
class TcpServer extends BaseObject
{
    /**
     * @var string 监听主机
     */
    public $host = '127.0.0.1';
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
     * bool $reuse_port
     * @var bool
     * @date 2022-09-02
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public $reuse_port = false;

    public $pools = null;

    public $ProcessNum = 1;

    /**
     * @var array 服务器选项
     */
    public $options = [
        'worker_num' => 2,
        'daemonize' => 0,
        'task_worker_num' => 2,
        'daemonize' => false, // 守护进程执行
        'task_worker_num' => 4, //task进程的数量
        'ssl_cert_file' => '',
        'ssl_key_file' => '',
        'pid_file' => '',
        'log_file' => '',
        'log_level' => 0,
    ];
    /**
     * @var array 应用配置
     */
    public $app = [];
    /**
     * @var \Swoole\Http\Server swoole server实例
     */
    public $server;

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (empty($this->app)) {
            throw new InvalidConfigException('The "app" property mus be set.');
        }

        $this->ContextInit(0);
        //多进程管理模块
        $this->pools = new Process\Pool($this->ProcessNum);
        //让每个OnWorkerStart回调都自动创建一个协程
        $this->pools->set($this->options);
        $this->pools->on('workerStart', function ($pool, $id) {
            //每个进程都监听9501端口
            $this->server = new Swoole\Coroutine\Server($this->host, $this->port, false, $this->reuse_port);

            //收到15信号关闭服务
            Process::signal(SIGTERM, function () {
                $this->shutdown();
            });

            //接收到新的连接请求 并自动创建一个协程
            $this->server->handle(function (Connection $conn) {
                $this->handles($conn);
            });

            //开始监听端口
            $this->start();

        });

        $this->poolStart();

    }

    /**
     * 上下文初始化.
     *
     * @return void
     * @date 2022-09-04
     *
     * @example
     *
     * @author Li Jinfang
     *
     * @since
     */
    public function ContextInit($type)
    {

    }

    public function handles(Connection $conn)
    {

        while (true) {
            //接收数据
            $data = $conn->recv(1);
            if ($data === '' || $data === false) {
                $errCode = swoole_last_error();
                $errMsg = socket_strerror($errCode);
                echo "errCode: {$errCode}, errMsg: {$errMsg}\n";
                $conn->close();
                break;
            }
            //发送数据
            $this->send($conn, 'hello');
            $this->messageReturn($conn);
            Coroutine::sleep(1);
        }
    }

    public function shutdown()
    {
        $this->server->shutdown();
    }

    // 系统校验后自己处理
    public function messageReturn(Connection $conn)
    {

    }

    public function send(Connection $conn, $content)
    {
        if ($content) {
            $conn->send($content);
        }
    }

    /**
     * 服务运行入口.
     */
    public function run()
    {
        global $argv;
        if (!isset($argv[0], $argv[1])) {
            print_r('invalid run params,see help,run like:php http-server.php start|stop|reload' . PHP_EOL);

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

            return $this->server->start();
        } elseif ($command == 'stop') {
            if (!empty($masterPid)) {
                posix_kill($masterPid, SIGTERM);
                if (PHP_OS == 'Darwin') {
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
     * 启动服务器.
     *
     * @return bool
     */
    public function start()
    {
        return $this->server->start();
    }

    /**
     * 关闭服务
     * @param Type|null $var
     * @return void
     * @date 2022-09-07
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function close()
    {
        return $this->server->close();
    }

    /**
     * 启动进程管理.
     *
     * @return bool
     */
    public function poolStart()
    {
        return $this->pools->start();
    }

    /**
     * 得到当前连接的 Socket 对象
     * @return void
     * @date 2022-09-07
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function exportSocket()
    {
        return $this->server->exportSocket();

    }
}
