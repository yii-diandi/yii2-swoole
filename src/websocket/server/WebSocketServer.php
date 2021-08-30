<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:39
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-08-30 19:13:05
 */

namespace diandi\swoole\websocket\server;

use diandi\swoole\web\Application;
use Yii;
use diandi\swoole\websocket\live\Room;
use diandi\swoole\websocket\live\RoomMap;
use diandi\swoole\websocket\live\RoomMember;
use Throwable;
use yii\base\BaseObject;

/**
 * 长连接
 *
 * Class WebSocketServer
 * @package console\controllers
 */
class WebSocketServer extends BaseObject
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


    public $type = 'ws';

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

            if ($this->type == 'ws') {
                $this->server = new \Swoole\WebSocket\Server($this->host, $this->port, $this->mode, $this->sockType);
            } else {
                $this->server = new \Swoole\WebSocket\Server($this->host, $this->port, $this->mode, $this->sockType | SWOOLE_SSL);
            }



            $this->server->set($this->options);
        }

        foreach ($this->events() as $event => $callback) {
            if (method_exists($this, 'on' . $event)) {
                $this->server->on($event, $callback);
            }
        }

        /************ 测试用可自行删除在别的地方引用 ***************/
        // 创建房间
        // Room::set(10001);
        // // 清理房间用户缓存
        // RoomMember::release(10001);
        // // 清理全部用户所在房间列表
        // RoomMap::release();
        /************ 测试用可自行删除在别的地方引用 ***************/
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


        $masterPid     = file_exists($pidFile) ? file_get_contents($pidFile) : null;
        if ($command == 'start') {
            if ($masterPid > 0 and posix_kill($masterPid, 0)) {
                print_r('Server is already running. Please stop it first.' . PHP_EOL);
                exit;
            }
            return $this->server->start();
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
    public function onStart(\Swoole\WebSocket\Server $server)
    {
        printf("listen on %s:%d\n", $this->host, $this->port);
    }

    /**
     * 工作进程启动时实例化框架
     * @param \Swoole\Http\Server $server
     * @param int $workerId
     * @throws InvalidConfigException
     */
    public function onWorkerStart(\Swoole\WebSocket\Server $server, $workerId)
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
    public function onWorkerError(\Swoole\WebSocket\Server $server, $workerId, $workerPid, $exitCode, $signal)
    {
        fprintf(STDERR, "worker error. id=%d pid=%d code=%d signal=%d\n", $workerId, $workerPid, $exitCode, $signal);
    }


    /**
     * 开启连接
     *
     * @param $server
     * @param $frame
     */
    public function onOpen(\Swoole\WebSocket\Server $server, $frame)
    {
        echo "server: handshake success with fd{$frame->fd}\n";
        echo "server: {$frame->data}\n";

        // 验证token进行连接判断
    }

    /**
     * 消息
     * @param $server
     * @param $frame
     * @throws \Exception
     */
    public function onMessage(\Swoole\WebSocket\Server $server, $frame)
    {
        if (!($message = json_decode($frame->data, true))) {
            echo "没有消息内容" . PHP_EOL;
            return true;
        }

        // 判断房间id
        if (!isset($message['room_id']) && in_array($message['type'], ['login'])) {
            throw new \Exception("room_id not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$frame->data");
        }

        // 输出调试信息
        echo $frame->data . PHP_EOL;

        // 业务逻辑
        switch ($message['type']) {
                // 心跳
            case 'pong':

                return true;

                break;

                // 进入房间(登录)
            case 'login':

                $member = [
                    'fd' => $frame->fd,
                    'room_id' => $message['room_id'],
                    'member_id' => $message['member']['member_id'],
                    'nickname' => $message['member']['nickname'],
                    'head_portrait' => $message['member']['head_portrait'],
                ];

                // 加入全部列表
                RoomMap::set($frame->fd, $message['room_id']);
                // 加入房间列表
                RoomMember::set($message['room_id'], $frame->fd, $member);
                // RoomMember::release($message['room_id']);
                $lists = RoomMember::list($message['room_id']);
                foreach ($lists as $key => $value) {
                    $list[] = json_decode($value, true);
                }

                // 转发给自己获取在线列表
                $this->server->push($frame->fd, $this->singleMessage('list', $frame->fd, $frame->fd, [
                    'list' => $list,
                ]));

                // 转播给当前房间的所有客户端
                $this->server->task($this->massMessage($message['type'], $frame->fd, [
                    'count' => RoomMember::count($message['room_id']),
                    'member' => $member,
                ]));

                break;

                // 评论消息
            case 'say':

                // 私聊
                if ($message['to_client_id'] != 'all') {
                    // 私发
                    $this->server->push($message['to_client_id'], $this->singleMessage($message['type'], $frame->fd, $message['to_client_id'], [
                        'content' => nl2br(htmlspecialchars($message['content'])),
                    ]));

                    return true;
                }

                // 广播消息
                $this->server->task($this->massMessage($message['type'], $frame->fd, [
                    'content' => nl2br(htmlspecialchars($message['content'])),
                ]));

                break;

                // 礼物
            case 'gift':

                // 广播消息
                $this->server->task($this->massMessage($message['type'], $frame->fd, [
                    'gift_id' => $message['gift_id'],
                ]));

                break;
                // 离开房间
            case 'leave':
                $fd = $message['fd'];
                if ($room_id = RoomMap::get($fd)) {
                    // 删除
                    RoomMember::del($room_id, $fd);

                    // 推送退出房间
                    $this->server->task($this->massMessage($message['type'], $frame->fd, [
                        'count' => RoomMember::count($room_id),
                    ]));
                }

                break;
        }

        return true;
    }

    /**
     * 关闭连接
     *
     * @param $server
     * @param $fd
     */
    public function onClose(\Swoole\WebSocket\Server $server, $fd)
    {
        echo "client {$fd} closed" . PHP_EOL;

        // 验证是否进入房间，如果有退出房间列表
        if ($room_id = RoomMap::get($fd)) {
            // 删除
            RoomMember::del($room_id, $fd);
            // 推送退出房间
            $this->server->task($this->massMessage('leave', $fd, [
                'count' => RoomMember::count($room_id),
            ]));
        }
    }

    /**
     * 处理异步任务
     *
     * @param $server
     * @param $task_id
     * @param $from_id
     * @param $data
     */
    public function onTask(\Swoole\WebSocket\Server $server, $task_id, $from_id, $data)
    {
        $this->server->finish($data);
    }

    /**
     * 处理异步任务的结果
     *
     * @param $server
     * @param $task_id
     * @param $data
     */
    public function onFinish(\Swoole\WebSocket\Server $server, $task_id, $data)
    {
        // 根据 fd 下发房间通知
        $sendData = json_decode($data, true);
        $room_id = RoomMap::get($sendData['from_client_id']);
        $list = RoomMember::list($room_id);
        unset($sendData, $room_id);

        //广播
        foreach ($list as $val) {
            $info = json_decode($val, true);
            $this->server->push($info['fd'], $data);

            unset($info);
        }

        echo "AsyncTask[$task_id] 完成: $data" . PHP_EOL;
    }

    /**
     * 群发消息
     *
     * @param $type
     * @param $from_client_id
     * @param array $otherArr
     * @return string
     */
    protected function massMessage($type, $from_client_id, array $otherArr = [])
    {
        $message = array_merge([
            'type' => $type,
            'from_client_id' => $from_client_id,
            'to_client_id' => 'all',
            'time' => date('Y-m-d H:i:s'),
        ], $otherArr);

        return json_encode($message);
    }

    /**
     * 单发消息
     *
     * @param $type
     * @param $from_client_id
     * @param $to_client_id
     * @param array $otherArr
     * @return string
     */
    protected function singleMessage($type, $from_client_id, $to_client_id, array $otherArr = [])
    {
        $message = array_merge([
            'type' => $type,
            'from_client_id' => $from_client_id,
            'to_client_id' => $to_client_id,
            'time' => date('Y-m-d H:i:s'),
        ], $otherArr);

        return json_encode($message);
    }
}
