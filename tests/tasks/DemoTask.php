<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2021-01-19 20:38:39
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2021-01-20 11:57:10
 */

namespace swooleService\tasks;


class DemoTask
{
    public function demo($a, $b)
    {
        printf("a:%s b:%s\n", $a, $b);
        return 'ok';
    }
}