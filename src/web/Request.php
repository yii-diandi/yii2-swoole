<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-02 17:13:12
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-16 11:13:24
 */

/**
 * @author xialeistudio
 * @date 2019-05-17
 */

namespace diandi\swoole\web;

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
        $this->headers->removeAll();
        foreach ($this->_request->header as $name => $value) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
            $this->headers->add($name, $value);
        }
    }

    /**
     * initialized global vars
     */
    protected function setupGlobalVars()
    {
        $_GET = $this->_request->get ?: [];
        $_POST = $this->_request->post ?: [];
        $_COOKIE = $this->_request->cookie;
        foreach ($this->_request->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }
        $this->setBodyParams(null);
        $this->setRawBody($this->_request->rawContent());
        $this->setPathInfo($_SERVER['PATH_INFO']);
    }
}