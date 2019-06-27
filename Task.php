<?php
namespace XXXcrawler;
/**
 * 任务列表及状态管理
 */
class Task implements iTask {
    private $states = [];
    private $times = [];
    // 获取全部任务，缺省实现，从配置中读取
    function getAll() {
        $f = ROOT.'/task.conf.php';
        $list = [];
        if(!file_exists($f)) plog("Error: 任务列表读取失败");
        else {
            $list = require $f;
        }
        return $list;
    }
    function getState($taskId) {
        return $this -> states[$taskId] ?? STATES['DEFAULT'];
    }
    function getLastTime($taskId) {
        return $this -> times[$taskId] ?? 0;
    }
    function setState($taskId, $state) {
        $this -> states[$taskId] = $state;
        $this -> times[$taskId]  = time();
    }
}