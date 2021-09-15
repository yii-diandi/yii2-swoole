<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-19 20:34:19
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-21 22:27:16
 */
 
/**
 * @author xialeistudio
 * @date 2019-05-17
 */

namespace swooleService\controllers;

use diandi\addons\models\Bloc;
use swooleService\tasks\DemoTask;
use Yii;
use yii\db\Exception;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        Yii::$app->redis->hset('self::PREFIX_KEY', '$room_id', 4568);
        
        return [
            'name' => Yii::$app->name,
            'version' => Yii::$app->version
        ];
    }

    public function actionDump()
    {
        return [
            '$_GET' => Yii::$app->request->get(),
            '$_POST' => Yii::$app->request->post()
        ];
    }

    public function actionCache()
    {
        return [
            'data' => Yii::$app->cache->getOrSet('test', function () {
                return time();
            }, 10)
        ];
    }

    /**
     * @return array|false
     * @throws Exception
     */
    public function actionDb()
    {
        $list = Bloc::find()->asArray()->all();
        return $list;
        return Yii::$app->db->createCommand('SELECT VERSION() as version')->queryOne();
    }

    public function actionTask()
    {
        print_r(Yii::$app->server);
        Yii::$app->server->task([[DemoTask::class, 'demo'], ['a', '1']]);
        return [];
    }
}