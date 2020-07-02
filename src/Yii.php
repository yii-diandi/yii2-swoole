<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2018/1/15
 * Time: 下午5:26
 */

require(__DIR__ . '/BaseYii.php');

class Yii extends \diandi\swoole\BaseYii
{

}

spl_autoload_register(['Yii', 'autoload'], true, true);
Yii::$classMap = require(YII2_PATH . '/classes.php');