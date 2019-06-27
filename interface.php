<?php
// 接口定义
namespace XXXcrawler;

// 任务
interface iTask {
    /**
     * 获取任务列表
     * @return Array(task) , task= [id=> ,name=>, intval => ,state => , last_time => , data => ]
     */
    public function getAll();
    /**
     * 获取任务状态
     * @param Int $taskId 任务id
     * @return Int ()
     */
    public function getState($taskId);
    /**
     * 获取任务最后执行时间
     * @param Int $taskId 任务id
     * @return Int unixtime
     */
    public function getLastTime($taskId);
    /**
     * 设置任务状态
     * @param int $taskId 任务id
     * @param Int $state  状态
     */
    public function setState($taskId, Int $state);
}

// 下载器
interface iDownloader {
    /**
     * 下载页面内容
     * @param $urls = ['key' => 'url']
     * @param Array ['key' => 'pageData']
     */
    public function fetch(Array $urls);
}
// 页面内容提取
interface iParser {
    /**
     * 提取指定内容
     * @param Array $fields = ['fname' => 'path',...] 提取的字段及路径/模式
     * @param string $html 页面内容
     */
    public function find($fields, $html);
}