<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:39
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-09-19 20:50:51
 */

namespace diandi\swoole\websocket\server;

use diandi\swoole\websocket\Context;
use diandi\swoole\websocket\events\webSocketEvent;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Server;
use function Swoole\Coroutine\run;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use yii\base\Component;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * 长连接.
 *
 * Class WebSocketServer
 */
class WebSocketServer extends Component
{
    const EVENT_WEBSOCKET_BEFORE = 'websocket_berfore';

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

    public $type = 'ws';

    /**
     * bool $reuse_port.
     *
     * @var bool
     * @date 2022-09-02
     *
     * @example
     *
     * @author Wang Chunsheng
     *
     * @since
     */
    public $reuse_port = false;

    public $channel;

    public $channelListener;

    public $channelNum = 1;

    /**
     * Undocumented variable.
     *
     * @var [type]
     * @date 2022-09-19
     *
     * @example
     *
     * @author Wang Chunsheng
     *
     * @since
     */
    public $context;

    /**
     * @var array 服务器选项
     */
    public $options = [
        'worker_num' => 2,
        'daemonize' => 0,
        'task_worker_num' => 2,
        'daemonize' => false, // 守护进程执行
        'task_worker_num' => 4, //task进程的数量
        // 'ssl_cert_file' => '',
        // 'ssl_key_file' => '',
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

        // 给websocket启用一个通道
        $this->channel = new Channel($this->channelNum);
        // 给tcp启用一个通道
        $this->channelListener = new Channel($this->channelNum);
        $this->ContextInit();
        go(function () {
            $this->onBeforeEvent();
            if (!$this->server instanceof \Swoole\Coroutine\Http\Server) {
                if ($this->type == 'ws') {
                    $this->server = new Server($this->host, $this->port, false, $this->reuse_port);
                } else {
                    $this->server = new Server($this->host, $this->port, true, $this->reuse_port);
                }
                $this->server->set($this->options);
                $this->server->handle('/', function (Request $request, Response $ws) {
                    $objectId = spl_object_id($ws);
                    $wsObjects[$objectId] = $ws;
                    $this->context->addArray('wsObjects', $wsObjects);
                    if ($this->checkUpgrade($request, $ws)) {
                        $ws->upgrade();
                        $this->handles($request, $ws);
                    }
                });
            }
            $this->server->start();
        });

        go(function () {
            $this->addlistenerPort($this->channelListener);
        });
    }

    public function onBeforeEvent()
    {
        Event::on(webSocketEvent::className(), self::EVENT_WEBSOCKET_BEFORE, function ($event) {
            var_dump($event->cid);  // 显示 "null"
        });
    }

    public function checkUpgrade(Request $request, Response $ws)
    {
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
    public function ContextInit()
    {
        $this->context = new Context();
    }

    public function handles(Request $request, Response $ws)
    {
        while (true) {
            $frame = $ws->recv();
            if ($frame === '') {
                $this->close($request, $ws);
                break;
            } elseif ($frame === false) {
                echo 'errorCode: '.swoole_last_error()."\n";
                $this->close($request, $ws);
                break;
            } else {
                if ($frame->data == 'close' || get_class($frame) === CloseFrame::class) {
                    $this->close($request, $ws);
                    break;
                }
                $this->message($request, $ws);
            }
        }
    }

    /**
     * 扩展新的服务
     *
     * @return void
     * @date 2022-09-02
     *
     * @example
     *
     * @author Wang Chunsheng
     *
     * @since
     */
    public function addlistenerPort($channelListener)
    {
    }

    public function close(Request $request, Response $ws)
    {
        $this->destory($request, $ws);
        $ws->close();
    }

    public function destory(Request $request, Response $ws)
    {
    }

    /**
     * 响应消息处理 swoole-cli ./ddswoole socket/run --bloc_id=1 --store_id=1 --addons=diandi_watches.
     *
     * @return void
     * @date 2022-09-02
     *
     * @example
     *
     * @author Wang Chunsheng
     *
     * @since
     */
    private function message(Request $request, Response $ws)
    {
        $frame = $ws->recv();
        if (!($message = json_decode($frame->data, true))) {
            $ws->push($this->socketJson(401, 'ERROR', '消息内容必须是json字符串'));

            return false;
        }

        if (!$message['type']) {
            $ws->push($this->socketJson(401, 'ERROR', '消息类型type必须设置'));

            return false;
        }

        if ($this->heartbeat($ws, $message)) {
            return false;
        }

        $this->messageReturn($request, $ws, $message, $this->channel);
    }

    public function heartbeat($ws, $message)
    {
        if ($message['type'] === 'HEARTBEAT') {
            // 心跳
            $ws->push($this->socketJson(200, 'HEARTBEAT', '心跳成功'));

            return true;
        }

        return false;
    }

    // 系统校验后自己处理
    public function messageReturn(Request $request, Response $ws, $message, $channel)
    {
    }

    public function push(Request $request, Response $ws, string $data, $isMass = true)
    {
        if ($isMass) {
            $frame = $ws->recv();
            $ws->push("Hello 112 {$frame->data}!");
        }
    }

    /**
     * 服务运行入口.
     */
    public function run()
    {
        global $argv;
        if (!isset($argv[0], $argv[1])) {
            print_r('invalid run params,see help,run like:php http-server.php start|stop|reload'.PHP_EOL);

            return;
        }
        $command = $argv[1];

        $pidFile = $this->options['pid_file'];

        $masterPid = file_exists($pidFile) ? file_get_contents($pidFile) : null;
        if ($command == 'start') {
            if ($masterPid > 0 and posix_kill($masterPid, 0)) {
                print_r('Server is already running. Please stop it first.'.PHP_EOL);
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
                print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGTERM.'.PHP_EOL);
            }
            exit;
        } elseif ($command == 'reload') {
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
     * 启动服务器.
     *
     * @return bool
     */
    public function start()
    {
        return $this->server->start();
    }

    /**
     * 返回json字符串数据格式.
     *
     * @param int          $code    状态码
     * @param string       $message 返回的报错信息
     * @param array|object $data    返回的数据结构
     */
    private function socketJson($code, $type, $message = '', $data = [])
    {
        $result = [
            'type' => $type,
            'data' => [
                'code' => (int) $code,
                'message' => trim($message),
                'data' => $data ? ArrayHelper::toArray($data) : [],
            ],
        ];

        return json_encode($result);
    }
}
