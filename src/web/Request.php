<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-02 17:13:12
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-22 17:21:44
 */

/**
 * @author xialeistudio
 * @date 2019-05-17
 */

namespace diandi\swoole\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\Cookie;

/**
 * Swoole Request Proxy
 * Class Request
 * @package diandi\swoole\web
 */
class Request extends \yii\web\Request
{
    /**
     * @var \Swoole\Http\Request
     */
    private $_request;

    private $_blocId;

    private $_storeId;

    private $_accessToken;

    private $_addons;

    public $post;

    /**
     * @return \Swoole\Http\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @param \Swoole\Http\Request $request
     */
    public function setRequest($request)
    {
        $this->_request = $request;
        $this->setupHeaders();
        $this->setupGlobalVars();
    }

    /**
     * initialized request headers
     */
    protected function setupHeaders()
    {
        global $_GPC;

        $this->headers->removeAll();
        $get = !empty($this->_request->get) && is_array($this->_request->get) ? $this->_request->get : [];
        $post = !empty($this->_request->post) && is_array($this->_request->post) ? $this->_request->post : [];
        $_GPC = array_merge($get, $post);

        $this->_blocId = isset($_GPC['bloc_id']) ? (int) $_GPC['bloc_id'] : 0;
        $this->headers->add('bloc-id', $this->_blocId);
        $this->_storeId = isset($_GPC['store_id']) ? (int) $_GPC['store_id'] : 0;
        $this->headers->add('store-id', $this->_storeId);
        $this->_accessToken = isset($_GPC['access_token']) ? $_GPC['access_token'] : '';
        $this->headers->add('access-token', $this->_accessToken);
        
        // json数据返回所有数据
        $this->headers->add('Content-Type',"application/json; charset=utf-8");

        foreach ($this->_request->header as $name => $value) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
            $this->headers->add($name, $value);
        }
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
     * Converts `$_COOKIE` into an array of [[Cookie]].
     * @return array the cookies obtained from request
     * @throws InvalidConfigException if [[cookieValidationKey]] is not set when [[enableCookieValidation]] is true
     */
    protected function loadCookies()
    {
        $cookies = [];
        if ($this->enableCookieValidation) {
            if ($this->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($this) . '::cookieValidationKey must be configured with a secret key.');
            }
            $cookies = is_array($this->_request->cookie) ? $this->_request->cookie : [];
            foreach ($cookies as $name => $value) {
                if (!is_string($value)) {
                    continue;
                }
                $data = Yii::$app->getSecurity()->validateData($value, $this->cookieValidationKey);
                if ($data === false) {
                    continue;
                }
                $data = @unserialize($data);
                if (is_array($data) && isset($data[0], $data[1]) && $data[0] === $name) {
                    $cookies[$name] = new Cookie([
                        'name' => $name,
                        'value' => $data[1],
                        'expire' => null,
                    ]);
                }
            }
        } else {
            foreach ($this->_request->cookie as $name => $value) {
                $cookies[$name] = new Cookie([
                    'name' => $name,
                    'value' => $value,
                    'expire' => null,
                ]);
            }
        }

        return $cookies;
    }

    /**
     * initialized global vars
     */
    protected function setupGlobalVars()
    {
        global $_GPC;
        $_GET = $this->_request->get ?: [];
        $_POST = $this->_request->post ?: [];
        $content = $this->_request->getContent()? json_decode($this->_request->getContent(),true) :[];
        print_r($content);
        $_GPC = array_merge($_GET, $_POST, $this->_request->header,$content);
       
        $this->_addons = isset($_GPC['addons']) ? $_GPC['addons'] : 'sys';

        $_COOKIE = $this->_request->cookie;
        foreach ($this->_request->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }
        $this->setBodyParams(null);
        $this->setRawBody($this->_request->rawContent());
        $this->setPathInfo($_SERVER['PATH_INFO']);
        $this->setGlobalServer();
    }

    public function setGlobalServer()
    {
        $access_token = $this->_accessToken;
        $bloc_id = $this->_blocId;
        $store_id = $this->_storeId;
        $addons = $this->_addons;
        if($access_token){
            Yii::$app->service->commonMemberService->setAccessToken($access_token);
        }

        if($bloc_id && $store_id && $addons){
            Yii::$app->service->commonGlobalsService->initId($bloc_id, $store_id, $addons);
            Yii::$app->service->commonGlobalsService->getConf($bloc_id);
        }
    }
}
