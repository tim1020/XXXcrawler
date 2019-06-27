<?php
namespace app;

class Task implements \XXXcrawler\iTask {
    function getAll() {
        // 从数据库读取全部任务
    }
    function getState($taskId) {
        // 获取指定任务id的状态值
    }
    function getLastTime($taskId) {
        // 获取指定任务的状态最后变更时间
    }
    function setState($taskId, $state) {
        // 更新指定任务的状态
    }
}