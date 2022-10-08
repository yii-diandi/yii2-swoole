<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-03-22 04:46:55
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-10-08 19:29:42
 */

namespace diandi\swoole\web;

use Throwable;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class Application
 * @package diandi\swoole\web
 * @property Request $request
 * @property Response $response
 * @property \Swoole\Http\Server $server
 */
class Application extends  \yii\web\Application
{
    /**
     * @var string 任务处理器命名空间
     */
    public $taskNamespace = 'app\\tasks';

    public $context;

    public function run()
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;
        } catch (Throwable $e) {
            Yii::$app->errorHandler->handleException($e);
            return 1;
        }
    }

    /**
     * @return \Swoole\Http\Server|mixed
     * @throws InvalidConfigException
     */
    public function getWebServer()
    {
        return $this->get('webServer');
    }

    public function getConnectionManager()
    {
        return $this->get('connectionManager');
    }

    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'connectionManager' => ['class' => 'tsingsun\swoole\pool\ConnectionManager'],
        ]);
    }

    public function handleRequest($Request)
    {
        
    }
}
