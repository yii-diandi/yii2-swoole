<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-26 12:59:45
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-09-21 12:03:04
 */

namespace diandi\swoole;

use diandi\addons\models\searchs\DdAddons;
use Yii;
use yii\base\BootstrapInterface;
use yii\web\UnauthorizedHttpException;

class Loader implements BootstrapInterface
{
    /**
     * 应用标识.
     */
    protected $id;

    protected $app;

    /**
     * @param \yii\base\Application $application
     *
     * @throws UnauthorizedHttpException
     * @throws \Exception
     */
    public function bootstrap($application)
    {
        global $_W, $_GPC;
        $_W = Yii::$app->params;
        $this->id = Yii::$app->id;
        if (Yii::$app->id == 'app-api') {
            $response = Yii::$app->response;
            if (Yii::$app->request->getMethod() == 'OPTIONS') {
                $response->data = 'options请求 快速响应';
                $response->statusCode = 200;
                $response->send();
                Yii::$app->end();
                die;
            }
        }

        // 命令行类的入口
        if (in_array(Yii::$app->id, ['app-console'])) {
            // 迁移不执行相关的全局方法
            $argvStr = implode(',', $_SERVER['argv']);
            $argvs = $this->getArgv($_SERVER['argv']);
            if (isset($argvs['--app'])) {
                $this->app = $argvs['--app'];
            }
            if (strpos($argvStr, 'migrate') == false && strpos($argvStr, 'install') == false) {
                $this->afreshLoad(isset($argvs['--bloc_id']) ?? 0, isset($argvs['--store_id']) ?? 0, isset($argvs['--addons']) ?? 0);
            }
        } else {
            $_GPC = array_merge(Yii::$app->request->get(), Yii::$app->request->post());

            // 全局获取 优先从头部获取
            $bloc_id = Yii::$app->request->headers->get('bloc-id', 0);

            $store_id = Yii::$app->request->headers->get('store-id', 0);

            $access_token = Yii::$app->request->headers->get('access-token', 0);

            $addons = Yii::$app->request->headers->get('addons', '');

            if (empty($access_token)) {
                $access_token = isset($_GPC['access-token']) ? $_GPC['access-token'] : 0;
                // Yii::$app->request->get('bloc_id', 0);
            }
            if (empty($bloc_id)) {
                $bloc_id = isset($_GPC['bloc_id']) ? $_GPC['bloc_id'] : 0;
                // Yii::$app->request->get('bloc_id', 0);
            }
            if (empty($store_id)) {
                $store_id = isset($_GPC['store_id']) ? $_GPC['store_id'] : 0;
                //Yii::$app->request->get('store_id', 0);
            }

            // 如果提交的参数与头部不同，需要覆盖，方便扩展使用
            if (empty($_GPC['bloc_id'])) {
                $_GPC['bloc_id'] = $bloc_id;
            }

            if (empty($_GPC['store_id'])) {
                $_GPC['store_id'] = $store_id;
            }

            if (empty($addons)) {
                $addons = Yii::$app->request->get('addons', '');
            }

            if ($access_token) {
                Yii::$app->service->commonMemberService->setAccessToken($access_token);
            }
            $this->afreshLoad($bloc_id, $store_id, $addons);
        }
    }

    /**
     * 重载配置.
     *
     * @throws UnauthorizedHttpException
     */
    public function afreshLoad($bloc_id, $store_id, $addons)
    {
        try {
            $this->dbPools();
            Yii::$app->service->commonGlobalsService->initId($bloc_id, $store_id, $addons);
            Yii::$app->service->commonGlobalsService->getConf($bloc_id);
            // 初始化模块
            Yii::$app->setModules($this->getModulesByAddons());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取模块.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function getModulesByAddons()
    {
        // 系统已经安装的
        $DdAddons = new DdAddons();
        $addons = $DdAddons->find()->asArray()->all();
        $app_id = $this->id;
        $authListAddons = array_column($addons, 'identifie');
        $moduleFile = '';
        switch ($app_id) {
            case 'app-api':
                $moduleFile = 'api';
                break;
            case 'app-admin':
                $moduleFile = 'admin';
                break;
            case 'app-ddswoole':
                $moduleFile = 'api';
                break;
            case 'app-frontend':
                $moduleFile = 'frontend';
                break;
            case 'app-console':
                $moduleFile = 'console';
                break;
            default:
                $moduleFile = 'api';
        }

        $modules = [];
        $extendMethod = 'OPTIONS,';
        $extraPatterns = [];
        foreach ($authListAddons as $name) {
            $configPath = Yii::getAlias('@addons/'.$name.'/config/'.$moduleFile.'.php');
            if (file_exists($configPath)) {
                $config = require $configPath;
                if (!empty($config)) {
                    foreach ($config as $key => &$value) {
                        if (!empty($value['extraPatterns']) && is_array($value['extraPatterns'])) {
                            foreach ($value['extraPatterns'] as $k => $val) {
                                $newK = !(strpos($k, 'OPTIONS') === false) ? $k : $extendMethod.$k;
                                $extraPatterns[$newK] = $val;
                            }
                            $value['extraPatterns'] = $extraPatterns;
                        }
                    }

                    Yii::$app->getUrlManager()->addRules($config);

                    if (isset($config['controllerMap']) && is_array($config['controllerMap'])) {
                        foreach ($config['controllerMap'] as $key => $val) {
                            Yii::$app->controllerMap[$key] = $val;
                        }
                    }
                }
            }
            // 服务定位器注册
            $ClassName = 'addons\\'.$name.'\\'.$moduleFile;

            $modules[self::toUnderScore($name)] = [
                'class' => $ClassName,
            ];
        }

        return $modules;
    }

    public static function toUnderScore($str)
    {
        $array = [];
        for ($i = 0; $i < strlen($str); ++$i) {
            if ($str[$i] == strtolower($str[$i])) {
                $array[] = $str[$i];
            } else {
                if ($i > 0) {
                    $array[] = '--';
                }

                $array[] = strtolower($str[$i]);
            }
        }

        return implode('', $array);
    }

    public function getArgv($argv)
    {
        $list = [];
        foreach ($argv as $key => $value) {
            if (strpos($value, '=') !== false) {
                list($k, $v) = explode('=', $value);
                if (!empty($v) && strpos($k, '--') !== false) {
                    $list[$k] = $v;
                }
            }
        }

        return $list;
    }

    // 重新载入sql配置，启用连接池
    public function dbPools()
    {
        if (in_array($this->app, ['ddswoole'])) {
            \Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);
            $db = require Yii::getAlias('@common/config/db.php');
            $db['class'] = 'ddswoole\db\Connection';
            Yii::$app->setComponents([
                'db' => $db,
            ]);
        }
    }
}
