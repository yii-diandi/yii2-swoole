<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2018/1/15
 * Time: 下午9:21
 */

namespace diandi\swoole\di;

use Yii;

/**
 * Class ContainerDecorator
 * @package diandi\swoole\di
 */
class ContainerDecorator
{
    function __get($name)
    {
        $container = $this->getContainer();
        return $container->{$name};
    }

    function __call($name, $arguments)
    {
        $container = $this->getContainer();
        return $container->$name(...$arguments);
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        return Yii::$context->getContainer();
    }
}