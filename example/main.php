<?php
// 使用例子： 入口文件

define('ROOT', __DIR__);    // 应用入口根目录

## XXXcrawler依赖workerman，请指定workerman所在目录
define('WORKERMAN_PATH', __DIR__.'/../../lib/Workerman/');

## 如有需要，可去掉相应注释并修改
// define('WORKER_COUNT',      10);                       // worker进程数
// define('WORKER_SOCKET',     'Text://0.0.0.0:12345');   // worker监听地址
// define('TASK_INTVAL',       10 * 60);                  // 任务默认时间间隔
// define('TASK_MAX_TIME',     20 * 60);                  // 任务最长运行时间
// define('LOG_PATH',          __DIR__.'/logs/');         // LOG目录
// define('APP_NS',            'app');                    // 要映射对象的命名空间

require_once '../bootstrap.php'; 
## 如有需要，定义Task,Downloader、Parser、Deliver对象的Mapper，替换内置的对象
## 这些对象需实现对应的XXXcrawler\i**接口
define('MAPPER', [
    //'Task'        => 'app\Task',         // 任务列表
    //'Downloader'  => app\Downloader(),   // 下载器
    //'Parser'      => app\Parser(),       // 数据解析获取
    //'Deliver'     => app\Deliver()       // 数据投递
]);

new XXXcrawler\Scheduler(10); // 参数为定时器调度时间间隔，缺省为10s
