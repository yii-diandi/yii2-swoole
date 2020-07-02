<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2020-07-02 03:14:08
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2020-07-02 03:14:08
 */
 
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2017/3/7
 * Time: 上午10:41
 */

namespace diandi\swoole\bootstrap;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use diandi\swoole\web\Application;
use yii\base\ExitException;
use Yii;
use yii\base\Event;

/**
 * Yii starter for swoole server
 * @package diandi\swoole\bootstrap
 */
class WebApp extends BaseBootstrap
{
    /**
     * @param SwooleRequest $request
     * @param SwooleResponse $response
     * @throws \Exception
     * @throws \Throwable
     */
    public function handleRequest($request, $response)
    {
        $app = new Application($this->appConfig);
        $app->getRequest()->setSwooleRequest($request);
        $app->getResponse()->setSwooleResponse($response);
        $app->on(Application::EVENT_AFTER_RUN, [$this, 'onHandleRequestEnd']);

        try {

            $app->beforeRun();

            $app->state = Application::STATE_BEFORE_REQUEST;
            $app->trigger(Application::EVENT_BEFORE_REQUEST);

            $app->state = Application::STATE_HANDLING_REQUEST;
            $response = $app->handleRequest($app->getRequest());

            $app->state = Application::STATE_AFTER_REQUEST;
            $app->trigger(Application::EVENT_AFTER_REQUEST);

            $app->state = Application::STATE_SENDING_RESPONSE;

            $response->send();

            $app->trigger(Application::EVENT_AFTER_RUN);

            $app->state = Application::STATE_END;

            return $response->exitStatus;

        } catch (ExitException $e) {
            $app->end($e->statusCode, isset($response) ? $response : null);
            $app->state = -1;
            return $e->statusCode;
        } catch (\Exception $exception) {
            $app->getErrorHandler()->handleException($exception);
            $app->state = -1;
            return false;
        } catch (\Throwable $throwable) {
            $app->getErrorHandler()->handleError($throwable->getCode(),$throwable->getMessage(),$throwable->getFile(),$throwable->getLine());
            return false;
        }
    }

    /**
     * 请求处理结束事件
     * @param Event $event
     */
    public function onHandleRequestEnd(Event $event)
    {
        /** @var Application $app */
        $app = $event->sender;
        if ($app->has('session', true)) {
            $app->getSession()->close();
        }
        if($app->state == -1){
            $app->getLog()->logger->flush(true);
        }
    }

}