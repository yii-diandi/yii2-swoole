<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:06
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-20 04:36:53
 */
 
namespace diandi\swoole\websocket\live;

use Yii;

/**
 * 房间用户关联表
 *
 * Class RoomMap
 * @package diandi\websocket
 */
class RoomMap
{
    const KEY = 'live:room:member:fd:';

    /**
     * @param $fd
     * @param $room_id
     */
    public static function set($fd, $room_id)
    {
        Yii::$app->redis->hset(self::KEY, $fd, $room_id);
    }

    /**
     * @param $fd
     * @return mixed
     */
    public static function get($fd)
    {
        return Yii::$app->redis->hget(self::KEY, $fd);
    }

    /**
     * @param $fd
     */
    public static function del($fd)
    {
        Yii::$app->redis->hdel(self::KEY, $fd);
    }

    /**
     * 用户总数量
     *
     * @return mixed
     */
    public static function count()
    {
        return Yii::$app->redis->hlen(self::KEY);
    }

    /**
     * @return mixed
     */
    public static function release()
    {
        return Yii::$app->redis->del(self::KEY);
    }
}