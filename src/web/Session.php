<?php
/**
 * Created by PhpStorm.
 * User: diandi
 * Date: 2017/3/20
 * Time: 下午6:22
 */

namespace diandi\swoole\web;

use Yii;

/**
 * Class Session
 * @package diandi\swoole\web
 */
class Session extends \yii\web\Session
{
    use SessionTrait;

    public function init()
    {
        parent::init();
//        register_shutdown_function([$this, 'close']);
        if ($this->getIsActive()) {
            Yii::warning('Session is already started in swoole', __METHOD__);
            $this->updateFlashCounters();
        }
    }
}