<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-20 03:20:00
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-20 04:37:25
 */
 
namespace diandi\swoole\websocket\live;

use Yii;
use diandi\swoole\websocket\live\RoomMember;

/**
 * 房间
 *
 * Class Room
 * @package diandi\websocket
 */
class Room extends \yii\redis\ActiveRecord
{
    const PREFIX_KEY = 'live:room';

    /**
     * 创建房间
     *
     * @param $room_id
     * @param string $title
     * @param string $cover
     * @param int $number
     * @return mixed
     */
    public static function set($room_id, $title = '测试', $cover = '', $number = 0)
    {
        Yii::$app->redis->hset(self::PREFIX_KEY, $room_id, json_encode([
            'room_id' => $room_id,
            'title' => $title,
            'cover' => $cover,
            'number' => $number,
        ]));
    }

    /**
     * 删除房间
     *
     * @param $room_id
     * @return mixed
     */
    public static function del($room_id)
    {
        // 释放房间列表
        RoomMember::release($room_id);

        return Yii::$app->redis->hdel(self::PREFIX_KEY, $room_id);
    }

    /**
     * 房间列表
     *
     * @param $room_id
     * @return mixed
     */
    public static function list()
    {
        return Yii::$app->redis->hvals(self::PREFIX_KEY);
    }

    /**
     * 房间总数量
     *
     * @return mixed
     */
    public static function count()
    {
        return Yii::$app->redis->hlen(self::PREFIX_KEY);
    }

    /**
     * 原子自增
     *
     * @param $room_id
     * @return mixed
     */
    public static function incr($room_id)
    {

    }

    /**
     * 原子自减
     *
     * @param $room_id
     * @return mixed
     */
    public static function decr($room_id)
    {

    }
}