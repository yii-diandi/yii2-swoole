<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:39
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-10-08 20:42:14
 */

namespace diandi\swoole\web\server;

use diandi\addons\models\DdAddons;
use diandi\swoole\web\Application;
use diandi\swoole\websocket\Context;
use diandi\swoole\websocket\events\webSocketEvent;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Server;
use function Swoole\Coroutine\run;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Yii;
use yii\base\Component;
use yii\base\ErrorHandler;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * web服务.
 *
 * Class WebSocketServer
 */
class WebServer extends Component
{
    const EVENT_WEBSOCKET_BEFORE = 'websocket_berfore';

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

    public $type = 'https';

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
     * 上下文初始化
     * @return void
     * @date 2022-10-08
     * @example
     * @author Wang Chunsheng
     * @since
     */
    public function ContextInit()
    {
        $this->context = new Context();
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

    public function close(Request $request, Response $response)
    {
        $this->destory($request, $response);
        $response->close();
    }

    public function destory(Request $request, Response $response)
    {
    }

   

    // 系统校验后自己处理
    public function messageReturn(Request $request, Response $ws, $message, $channel)
    {
    }

  


	/**
     * 服务运行入口.
     */
    public function run()
    {
        // 给websocket启用一个通道
        $this->channel = new Channel($this->channelNum);
        // 给tcp启用一个通道
        $this->channelListener = new Channel($this->channelNum);
        $this->ContextInit();
        $status = Coroutine::join([
             go(function () {
                 $this->onBeforeEvent();
                 if (!$this->server instanceof \Swoole\Coroutine\Http\Server) {
                     if ($this->type == 'https') {
                         $this->server = new Server($this->host, $this->port, false, $this->reuse_port);
                     } else {
                         $this->server = new Server($this->host, $this->port, true, $this->reuse_port);
                     }
                     $this->server->set($this->options);
                     $this->server->handle('/', function (Request $request, Response $Response) {
                         $objectId = spl_object_id($Response);
                         $wsObjects[$objectId] = $Response;
                         $this->context->addArray('wsObjects', $wsObjects);
                         $this->handles($request, $Response);
                     });
                 }
                 $this->start();
                //  $this->server->start();                    
             }),
             go(function () {
                 $this->addlistenerPort($this->channelListener);
             }),
         ]);

        var_dump($status, swoole_strerror(swoole_last_error(), 9));

        return $status;
    }

    /**
     * 启动服务器.
     *
     * @return bool
     */
    public function start()
    {
        try {
            @swoole_set_process_name("ddicms-webServer");
            
            $Application = new Application($this->app);
            // 初始化模块
            Yii::$app->setModules($this->getModulesByAddons());
            Yii::$app->set('webServer', $this->server);
        } catch (\Exception $e) {
            print_r("start yii error:" . ErrorHandler::convertExceptionToString($e) . PHP_EOL);
            $this->server->shutdown();
        }
        return $this->server->start();
    }

     /**
     * 获取模块.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function getModulesByAddons()
    {
        // 系统已经安装的
        $DdAddons = new DdAddons();
        $addons = $DdAddons->find()->asArray()->all();
   
        $authListAddons = array_column($addons, 'identifie');
        $moduleFile = 'api';
        $modules = [];
        $extendMethod = 'OPTIONS,';
        $extraPatterns = [];
        foreach ($authListAddons as $name) {
            $configPath = Yii::getAlias('@addons/' . $name . '/config/' . $moduleFile . '.php');
            if (file_exists($configPath)) {
                $config = require $configPath;
                if (!empty($config)) {
                    foreach ($config as $key => &$value) {
                        if (!empty($value['extraPatterns']) && is_array($value['extraPatterns'])) {
                            foreach ($value['extraPatterns'] as $k => $val) {
                                $newK = !(strpos($k, 'OPTIONS') === false) ? $k : $extendMethod . $k;
                                $extraPatterns[$newK] = $val;
                            }
                            $value['extraPatterns'] = $extraPatterns;
                        }
                    }

                    Yii::$app->getUrlManager()->addRules($config);

                    if (isset($config['controllerMap']) && is_array($config['controllerMap'])) {
                        foreach ($config['controllerMap'] as $key => $val) {
                            Yii::$app->controllerMap[$key] = $val;
                        }
                    }
                }
            }
            // 服务定位器注册
            $ClassName = 'addons\\' . $name . '\\' . $moduleFile;

            $modules[self::toUnderScore($name)] = [
                'class' => $ClassName,
            ];
        }
        return $modules;
    }

    
    public static function toUnderScore($str)
    {
        $array = [];
        for ($i = 0; $i < strlen($str); ++$i) {
            if ($str[$i] == strtolower($str[$i])) {
                $array[] = $str[$i];
            } else {
                if ($i > 0) {
                    $array[] = '--';
                }

                $array[] = strtolower($str[$i]);
            }
        }

        return implode('', $array);
    }

    public function handles(Request $request, Response $response)
    {
        // $response->end("<h1>Index</h1>");

      

        // global $_GPC;

        try {
            $server = $this->server;
           
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                $response->end();
                return;
            }else{
                // Context::put('swooleServer', $request_uri);
                Yii::$app->request->setRequest($request);
                Yii::$app->response->setResponse($response);
                $this->server->handle('/', function ($request, $response) use ($server) {
                    $request_uri = $request->server['request_uri'];
                    $router =  explode('/',ltrim($request_uri,'/'));
                    $addons = 'addons\\'.$router[0];
                    $controller = $router[1].'Controller';
                    $action = 'action'.$router[2];
                    $params = $router[3]??'';
                    $class = Yii::createObject($addons.'\api\\'.$controller);
                    $class->{$action}($params);
                    $response->end("<h1>Stop</h1>");
                    $this->server->shutdown();
                });
            }
            
            
            // if (Yii::$app->response->checkAccess($response)) {
            //     Yii::$app->run();
            //     Yii::$app->request->onEndRequest();
            // }
        } catch (\Throwable $throwable) {
            echo $throwable->getMessage();
        }
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
