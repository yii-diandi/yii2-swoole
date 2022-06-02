<?php

/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:12
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-09-02 17:59:57
 */

namespace diandi\swoole\websocket\live;

use Yii;
use diandi\swoole\websocket\live\Room;
use swoole_table;

/**
 * 房间用户操控
 *
 * Class RoomMember
 * @package diandi\websocket
 */
class RoomMember
{
    const PREFIX_ROOM = 'live:room:member:';

    /**
     * 加入用户
     *
     * @param $room_id
     * @param $fd
     * @param array $member
     */
    public static function set($room_id, $fd, array $member)
    {
        Yii::$app->redis->hset(self::PREFIX_ROOM . $room_id, $fd, json_encode($member));
    }

    /**
     * 删除用户
     *
     * @param $room_id
     * @param $fd
     * @return mixed
     */
    public static function del($room_id, $fd)
    {
        return Yii::$app->redis->hdel(self::PREFIX_ROOM . $room_id, $fd);
    }

    /**
     * @param $fd
     * @return mixed
     */
    public static function get($room_id, $fd)
    {
        return Yii::$app->redis->hget(self::PREFIX_ROOM . $room_id, $fd);
    }

    /**
     * 用户列表
     *
     * @param $room_id
     * @return mixed
     */
    public static function list($room_id)
    {
        return Yii::$app->redis->hvals(self::PREFIX_ROOM . $room_id);
    }

    /**
     * 用户总数量
     *
     * @return mixed
     */
    public static function count($room_id)
    {
        return Yii::$app->redis->hlen(self::PREFIX_ROOM . $room_id);
    }

    /**
     * @return mixed
     */
    public static function release($room_id)
    {
        return Yii::$app->redis->del(self::PREFIX_ROOM . $room_id);
    }
}
