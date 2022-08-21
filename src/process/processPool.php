<?php
/**
 * @Author: Wang chunsheng  email:2192138785@qq.com
 * @Date:   2022-06-10 13:46:19
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-08-16 16:56:48
 */
namespace diandi\swoole\process;

use Swoole\Process;

/**
 * 动态进程池，类似fpm
 * 动态新建进程
 * 有初始进程数，最小进程数，进程不够处理时候新建进程，不超过最大进程数.
 */

// 一个进程定时投递任务

/**
 * 1. tick
 * 2. process及其管道通讯
 * 3. event loop 事件循环.
 */
class processPool
{
    private $pool;

    /**
     * @var Process[] 记录所有worker的process对象
     */
    private $workers = [];

    /**
     * @var array 记录worker工作状态
     */
    private $used_workers = [];

    /**
     * @var int 最小进程数
     */
    private $min_woker_num = 5;

    /**
     * @var int 初始进程数
     */
    private $start_worker_num = 10;

    /**
     * @var int 最大进程数
     */
    private $max_woker_num = 20;

    /**
     * 进程闲置销毁秒数.
     *
     * @var int
     */
    private $idle_seconds = 5;

    /**
     * @var int 当前进程数
     */
    private $curr_num;

    /**
     * 闲置进程时间戳.
     *
     * @var array
     */
    private $active_time = [];

    public function __construct()
    {
        $this->pool = new Process(function () {
            // 循环建立worker进程
            for ($i = 0; $i < $this->start_worker_num; ++$i) {
                $this->createWorker();
            }
            echo '初始化进程数：'.$this->curr_num.PHP_EOL;
            // 每秒定时往闲置的worker的管道中投递任务
            swoole_timer_tick(1000, function ($timer_id) {
                static $count = 0;
                ++$count;
                $need_create = true;
                foreach ($this->used_workers as $pid => $used) {
                    if ($used == 0) {
                        $need_create = false;
                        $this->workers[$pid]->write($count.' job');
                        // 标记使用中
                        $this->used_workers[$pid] = 1;
                        $this->active_time[$pid] = time();
                        break;
                    }
                }
                foreach ($this->used_workers as $pid => $used) {
                    // 如果所有worker队列都没有闲置的，则新建一个worker来处理
                    if ($need_create && $this->curr_num < $this->max_woker_num) {
                        $new_pid = $this->createWorker();
                        $this->workers[$new_pid]->write($count.' job');
                        $this->used_workers[$new_pid] = 1;
                        $this->active_time[$new_pid] = time();
                    }
                }

                // 闲置超过一段时间则销毁进程
                foreach ($this->active_time as $pid => $timestamp) {
                    if ((time() - $timestamp) > $this->idle_seconds && $this->curr_num > $this->min_woker_num) {
                        // 销毁该进程
                        if (isset($this->workers[$pid]) && $this->workers[$pid] instanceof Process) {
                            $this->workers[$pid]->write('exit');
                            unset($this->workers[$pid]);
                            $this->curr_num = count($this->workers);
                            unset($this->used_workers[$pid]);
                            unset($this->active_time[$pid]);
                            echo "{$pid} destroyed\n";
                            break;
                        }
                    }
                }

                echo "任务{$count}/{$this->curr_num}\n";

                if ($count == 20) {
                    foreach ($this->workers as $pid => $worker) {
                        $worker->write('exit');
                    }
                    // 关闭定时器
                    swoole_timer_clear($timer_id);
                    // 退出进程池
                    $this->pool->exit(0);
                    exit();
                }
            });
        });

        $master_pid = $this->pool->start();
        echo "Master $master_pid start\n";

        while ($ret = Process::wait()) {
            $pid = $ret['pid'];
            echo "process {$pid} existed\n";
        }
    }

    /**
     * 创建一个新进程.
     *
     * @return int 新进程的pid
     */
    public function createWorker()
    {  
        $a = 12345678;
        str_replace('1','2',$a);
        $worker_process = new Process(function (Process $worker) {
            // 给子进程管道绑定事件
            swoole_event_add($worker->pipe, function ($pipe) use ($worker) {
                $data = trim($worker->read());
                if ($data == 'exit') {
                    $worker->exit(0);
                    exit();
                }
                echo "{$worker->pid} 正在处理 {$data}\n";
                sleep(5);
                // 返回结果，表示空闲
                $worker->write('complete');
            });
        });

        $worker_pid = $worker_process->start();

        // 给父进程管道绑定事件
        swoole_event_add($worker_process->pipe, function ($pipe) use ($worker_process) {
            $data = trim($worker_process->read());
            if ($data == 'complete') {
                // 标记为空闲
//        echo "{$worker_process->pid} 空闲了\n";
                $this->used_workers[$worker_process->pid] = 0;
            }
        });

        // 保存process对象
        $this->workers[$worker_pid] = $worker_process;
        // 标记为空闲
        $this->used_workers[$worker_pid] = 0;
        $this->active_time[$worker_pid] = time();
        $this->curr_num = count($this->workers);

        return $worker_pid;
    }
}
