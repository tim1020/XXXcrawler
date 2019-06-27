<?php
/**
 * 启动辅助
 */
defined('ROOT') || die("Error: 未定义ROOT常量\n");
defined('WORKERMAN_PATH') || die ("Error: XXXcrawler依赖Workerman, 请自行下载安装，并定义WORKERMAN_PATH常量指向workerman目录\n");
require_once WORKERMAN_PATH . '/Autoloader.php';
require_once __DIR__.'/interface.php';
require_once __DIR__.'/Deliver.php';
require_once __DIR__.'/Filter.php';

// 定义部分必要常量
defined('WORKER_COUNT')  || define('WORKER_COUNT',      10);                        // worker进程数
defined('WORKER_SOCKET') || define('WORKER_SOCKET',     'Text://127.0.0.1:12345');  // worker监听地址
defined('TASK_INTVAL')   || define('TASK_INTVAL',       10 * 60);                   // 任务默认时间间隔
defined('TASK_MAX_TIME') || define('TASK_MAX_TIME',     20 * 60);                   // 默认最长执行时间(在该时间内任务未正常完成也不执行新的任务)
defined('LOG_PATH')      || define('LOG_PATH',          __DIR__.'/logs/');          // LOG目录
defined('APP_NS')        || define('APP_NS',            'app');                     // 应用对象的命名空间

// 任务状态
define('STATES', [
    'DEFAULT'    => 0,  // 缺省值，未知状态
    'RUNNING'    => 1,  // 进行中
    'FINISH'     => 2,  // 已完成
    'STOPPED'    => 99, // 已停止，不再执行
]);
// 任务执行结果
define('RESULT', [
    'OK'            => 1,   // 成功
    'ERR_CMD'       => -1,  // 任务指令无效
    'ERR_DOWNLOAD'  => -2,  // 下载内容出错
    'ERR_PARSE'     => -3,  // 提取内容出错
    'ERR_FILTER'    => -4,  // 过滤器出错
    'ERR_DELIVER'   => -5,  // 投递出错
    'ERR'           => -99, // 未知错误
]);

//自动加载
spl_autoload_register(function($class){
    $class = str_replace('.','', $class); //安全过滤
    $path = explode('\\', $class);
    $ns = array_shift($path);
    if ($ns == 'XXXcrawler') {
        $prefix = __DIR__;
    } elseif($ns == APP_NS) {
        $prefix = ROOT;
    } else {
        return;
    }
    $classFile = $prefix. '/'. implode('/',$path).'.php';
    file_exists($classFile) && require $classFile;
});

function plog($format, ...$args) {
    $msg = sprintf("%s %s\n", date('Y-m-d H:i:s'), $format);
    $msg = sprintf($msg, ...$args);
    echo $msg;
}
