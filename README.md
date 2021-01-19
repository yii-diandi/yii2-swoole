# yii2 swoole extension
在swoole环境下运行Yii2应用。 

yii2-swoole基于Yii2组件化进行编程，对业务和Yii2无侵入性。

## 快速开始

1. 初始化Yii2应用
1. 安装扩展
    ```bash
    composer require yii-diandi/yii2-swoole
    ```
1. 新建服务器配置(`config/server.php`)
    ```php
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
       'app' => require __DIR__ . '/web.php', // 原来的web.php配置
       'options' => [
           'pid_file' => __DIR__ . '/../runtime/swoole.pid',
           'worker_num' => 2,
           'daemonize' => 0,
           'task_worker_num' => 2,
       ]
   ];
    ```
1. 新增启动脚本(`index.php`)
    ```php
    <?php
    /**
     * @author xialeistudio
     * @date 2019-05-17
     */
    
    use diandi\swoole\web\Server;
    use Swoole\Runtime;
    
    Runtime::enableCoroutine();
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', getenv('PHP_ENV') === 'development' ? 'dev' : 'prod');
    
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
    
    
    $config = require __DIR__ . '/config/server.php';
    $server = new Server($config);
    $server->start();
    ```
1. 启动应用
    ```bash
    php index.php
    ```
 
## 示例项目

**tests** 目录下有测试用的完整项目。

## TODO

+ [ ] 协程环境下兼容



接口文档
发送消息的格式全部以json字符串发过来

心跳
请求地址

    ws://[to/you/url]:9501
参数

参数名	说明
type	pong
无返回

进入房间
请求地址

ws://[to/you/url]:9501
参数

参数名	说明
type	login
room_id	房间id
user_id	用户id
nickname	用户昵称
head_portrait	用户头像
返回(1)

{
　　"type":"login",
　　"from_client_id":2,
　　"to_client_id":"all",
　　"time":"2018-04-10 16:41:29",
　　"count":"1",
　　"member":{
　　　　"fd":2,
　　　　"room_id":10001,
　　　　"user_id":1,
　　　　"nickname":"隔壁老王",
　　　　"head_portrait":"123"
　　}
}
返回(2)

当前登录的人还会返回一个在线列表

{
　　"type":"list",
　　"from_client_id":2,
　　"to_client_id":2,
　　"time":"2018-04-10 16:41:29",
　　"list":[{
     　　"fd":2,
     　　"room_id":10001,
     　　"user_id":1,
     　　"nickname":"隔壁老王",
     　　"head_portrait":"123"
     }]
}
发言
请求地址

ws://[to/you/url]:9501
参数

参数名	说明
type	say
to_client_id	对谁说话:默认 all
content	内容
返回

{
　　"type":"say",
　　"from_client_id":2,
　　"to_client_id":"all",
　　"time":"2018-04-10 16:43:00",
　　"content":"123"
}
送礼物
请求地址

ws://[to/you/url]:9501
参数

参数名	说明
type	gift
gift_id	礼物id
返回

{
	"type": "gift",
	"from_client_id": 4,
	"to_client_id": "all",
	"gift_id": "礼物id",
	"time": "2018-03-06 11:27:15"
}
离开房间
请求地址

ws://[to/you/url]:9501
参数

参数名	说明
type	leave
返回

{
	"type": "leave",
	"from_client_id": 1,
	"to_client_id": "all",
	"count": 2,
	"time": "2018-03-06 11:27:15"
}