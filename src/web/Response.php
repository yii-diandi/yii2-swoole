<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-02 17:13:12
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-22 17:07:54
 */

/**
 * @author xialeistudio
 * @date 2019-05-17
 */

namespace diandi\swoole\web;

use common\helpers\ResultHelper;
use swooleService\models\SwooleAccessToken;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Swoole Response Proxy
 * Class Response
 * @package diandi\swoole\web
 */
class Response extends \yii\web\Response
{
    /**
     * @var \Swoole\Http\Response
     */
    private $_response;
    
    public $content;
    
    public $fd;

    

    /**
     * @return \Swoole\Http\Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param \Swoole\Http\Response $response
     */
    public function setResponse($response)
    {
        $this->_response = $response;
    }

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    protected function sendHeaders()
    {
        foreach ($this->getHeaders() as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            foreach ($values as $value) {
                $this->_response->header($name, $value);
            }
        }
        $this->_response->status($this->getStatusCode());
        $this->sendCookies();
    }

    

    public function detach()
    {
        return $this->_response->detach();
    }


    public function isWritable()
    {
        return $this->_response->isWritable();
    }

    public function checkAccess($response)
    {
        // 验证头部是否传递了access-token
   
        // $SwooleAccessToken = new SwooleAccessToken();
        // if(empty($headers['access-token'])){
        //     ResultHelper::httpJson(401,'access-token不能为空');
        //     return false;
        // }

        // if(empty($headers['store-id'])){
        //     ResultHelper::httpJson(401,'store_id不能为空');
        //     return false;
        // }

        // if(empty($headers['access-token'])){
        //     ResultHelper::httpJson(401,'access-token不能为空');
        //     return false;
        // }
        
        // $swooleMember = $SwooleAccessToken::findIdentityByAccessToken($headers['access-token']);

        // if(key_exists('code',$swooleMember)){
        //     $this->content = json_encode(['code'=>$swooleMember['code'],'message'=>$swooleMember['message']]);
        //     $this->sendContent();
        //     return false;
        // }

        return true;
    }

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    protected function sendCookies()
    {
        if ($this->getCookies() === null) {
            return [];
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey)) {
                /** @noinspection PhpUndefinedMethodInspection */
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            $this->_response->cookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * @inheritDoc
     */
    public function sendContent()
    {
        if ($this->stream === null) {
            $this->_response->end($this->content);
            return;
        }

        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                $this->_response->write(fread($handle, $chunkSize));
            }
            fclose($handle);
            return;
        } else {
            while (!feof($this->stream)) {
                $this->_response->write(fread($this->stream, $chunkSize));
            }
            fclose($this->stream);
        }
        $this->_response->end();
    }

    
    /**
     * Sends the response to the client.
     */
    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }
    
}