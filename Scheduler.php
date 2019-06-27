<?php
namespace XXXcrawler;

use \Workerman\Worker;
use \Workerman\Lib\Timer;
use \Workerman\Connection\AsyncTcpConnection;

/**
 * 任务调度器（启用定时器，按任务设置定时执行任务）
 */
class Scheduler {
    private $intval;
    private $task;
    private $downloader;
    private $parser;

    function __construct(Int $intval = 10) {
        plog(">>> app start >>> ");
        $this -> intval = $intval;

        if(!file_exists(LOG_PATH)) mkdir(LOG_PATH, 0755, 1);
        Worker::$logFile    = LOG_PATH.'/workerman.log';
        Worker::$pidFile    = LOG_PATH.'/workerman.pid';
        Worker::$stdoutFile = LOG_PATH. '/app.log';
        
        $this -> _builder();
        $this -> _startWorker();
        $this -> _startTimer();

        Worker::runAll();
    }

    /**
     * 开启工作进程，监听处理任务
     */
    private function _startWorker() {
        $w = new Worker(WORKER_SOCKET);
        $w -> count = WORKER_COUNT;
        $w -> name  = 'XXXcrawlerWorker';
        //只有php7才支持task->reusePort，可以让每个task进程均衡的接收任务, 当前有bug,加上后worker会被阻塞，待确认
        // $worker -> reusePort = true;
        $w -> onMessage = function($conn, $conf) {
            $conf = json_decode($conf, true);
            $id     = $conf['id'] ?? 0;
            $cmd    = $conf['data'] ?? [];

            $result = RESULT['ERR'];
            plog("excute: task_id=%s", $id);
            if(!$cmd) {
                $result = RESULT['ERR_CMD'];
                plog('Error: task_id=%s,msg=%s', $id, '任务指令无效');
            } else {
                // 工作流程
                $result = $this -> _workFlow($cmd, $id);
            }
            $conn -> send($result);
        };
    }
    /**
     * 开启定时器，定时扫描分派任务
     */
    private function _startTimer() {
        $w = new Worker();
        $w -> name  = 'XXXcrawlerTimer';
        $w -> onWorkerStart = function() {
            Timer::add($this -> intval, function() {
                plog('>>>>>> timer on >>>>>>');
                /**
                 * $re = ['id' => '', 'intval' =>'60', 'data' => Array];
                 */
                foreach($this -> task -> getAll() as $re) {
                    $id     = $re['id'] ?? 0;
                    $intval = isset($re['intval']) ? $re['intval'] : TASK_INTVAL;     // 默认任务间隔
                    $max    = $re['max_time'] ?? TASK_MAX_TIME; // 默认最长执行时间
                    if(!$id) {
                        plog('Error: task id invalid');
                        continue;
                    }
                    // 判断任务是否可以跑
                    $state = $this -> task -> getState($id);
                    plog('state: task_id=%s, state=%s', $id, $state);
                    // 已被终止的任务
                    if($state == STATES['STOPPED']) {
                        plog("stopped: task_id=%s", $id);
                        continue;
                    }
                    // 时间间隔未到
                    if(time() < $this -> task -> getLastTime($id) + $intval) {
                        plog("waitting: task_id=%s", $id);
                        continue;
                    }
                    // 时间间隔已到，但上一次任务还在跑，且未超过最长执行时间
                    if($state == STATES['RUNNING'] && time() < $this -> task -> getLastTime($id) +  TASK_MAX_TIME) { // 正在跑，且时间正常
                        plog("running: task_id=%s", $id);
                        continue;
                    }

                    // 分派任务
                    $this -> task -> setState($id, STATES['RUNNING']);
                    $conn = new AsyncTcpConnection(WORKER_SOCKET);
                    $ts = microtime(1);
                    $conn -> send(json_encode($re));
                    
                    // 异步获取结果
                    $conn -> onMessage = function($conn, $result) use ($id, $intval, $ts){
                        $time = round(microtime(1) - $ts , 3);
                        if($result == RESULT['OK']) {
                            plog("success: task_id=%s,es_time=%s", $id, $time);
                        } else {
                            plog("fail: task_id=%s,es_time=%s,result=%s", $id, $time, $result);
                        }
                        if($intval == 0) {
                            $this -> task -> setState($id, STATES['STOPPED']);
                        } else {
                            $this -> task -> setState($id, STATES['FINISH']);
                        }
                        $conn -> close();
                    };
                    // 执行异步连接
                    $conn -> connect();
                }
            });
        };
    }

    /**
     * class builder 创建爬虫处理核心类
     */
    private function _builder() {
        $task       = MAPPER['Task'] ?? 'XXXcrawler\Task';
        $downloader = MAPPER['Downloader'] ?? 'XXXcrawler\Downloader';
        $parser     = MAPPER['Parser'] ?? 'XXXcrawler\Parser';

        $this -> task       = new $task;
        if(! ($this -> task instanceof iTask)) {
            exit("Error: Task必须实现XXXcrawler\iTask接口\n");
        }

        $this -> downloader = new $downloader;
        if(! ($this -> downloader instanceof iDownloader)) {
            exit("Error: Downloader必须实现XXXcrawler\iDownloader接口\n");
        }

        $this -> parser     = new $parser;
        if(! ($this -> parser instanceof iParser)) {
            exit("Error: Parser必须实现XXXcrawler\iParser接口\n");
        }
    }

    // 执行流程: Downloader -> Parser -> Filter -> Deliver
    /**
     * @param Array $cmd = [
     *      'url'    => '入口地址',
     *      'fileds' => [ // 提取的字段
     *          'fname1' => 'xpath: XXX',    // xpath方式获取
     *          'fname2' => 'css: xxx',      // css selector方式
     *          'fname3' => 'regular:xxx',   // 正则表达式
     *       ],
     *       'sub' => [ // 下级页面
     *          'url' => 'fname1' // 使用上级获得的字段
     *          'fields' => [],
     *          'save'   => [],
     *          'next'  => ''
     *       ],
     *       'save' => [ // 保存设置
     *          'filter' => [ // 过滤器，按顺序执行
     *              'filter1' => ['param1', 'param2'..],'filter2' => []
     *          ],
     *          'deliver' => [ // 投递，可多个
     *              'deliver1' => ['param1'...],
     *              'deliver2' => []
     *          ]
     *       ],
     *      'next' => '' // 翻页，规则与提取字段一样，支持xpath,css,regular
     * ]
     */
    private function _workflow($cmd, $taskId) {
        //TODO: 支持单一任务内并发
        if(!is_array($cmd) || empty($cmd['url']) || empty($cmd['fields']) ) {
            plog('Error: task_id=%s, msg=%s',$taskId, '任务指令错误');
            return RESULT['ERR_CMD'];
        }
        $html = $this -> downloader -> fetch($cmd['url']);
        if(!$html) {
            plog('Error: task_id=%s, msg=下载%s失败',$taskId, $cmd['url']);
            return RESULT['ERR_DOWNLOAD'];
        }
        $data = $this -> parser -> find($cmd['fields'], $html);
        if(!$data) {
            plog('Error: task_id=%s, msg=%s',$taskId, '提取数据失败');
            return RESULT['ERR_PARSE'];
        }
        if(empty($data['_base'])) {
            $r = parse_url($cmd['url']);
            $data['_base'] = sprintf('%s://%s%s/',$r['scheme'], $r['host'], empty($r['port'])? '' : ':'.$r['port']);
        }
        $baseUrl = $data['_base'];
        // 过滤
        if(!empty($cmd['save']['filter'])) {
            foreach($cmd['save']['filter'] as $k => $v) {
                $func = 'filter'.$k;
                if(!function_exists($func)) {
                    plog('Error: task_id=%s,msg=filter %s not found', $taskId, $func);
                    return RESULT['ERR_FILTER'];
                }
                $data = call_user_func($func, $data, ...$v);
            }
        }
        // 投递
        if(!empty($cmd['save']['deliver'])) {
            foreach($cmd['save']['deliver'] as $k => $v) {
                $func = 'deliver'.$k;
                if(!function_exists($func)) {
                    plog('Error: task_id=%s,msg=deliver %s not found', $taskId, $func);
                    return RESULT['ERR_FILTER'];
                }
                $re = call_user_func($func, $data, ...$v);
                if(!$re) {
                    plog('Error: task_id=%s,msg=deliver to %s fail', $taskId, $k);
                }
            }
        }

        // 子页面
        if(!empty($cmd['sub'])) {
            $sCmd = $cmd['sub'];
            $items = $data[$sCmd['url']];
            foreach($items as $item) {
                if(strpos($item, 'http') !== 0) {
                    $sCmd['url'] = $baseUrl. $item;
                } else $sCmd['url'] = $item;
                $this -> _workflow($sCmd, $taskId);
            }
        }

        // 翻页
        if(!empty($cmd['next'])) { 
            $next = $this -> _getNextUrl($cmd['next'], $html);
            if($next) {
                if(strpos($next, 'http') !== 0) {
                    $next = $baseUrl. $next;
                }
                $cmd['url'] = $next;
                plog('goNext: task_id=%s,url=%s', $taskId, $next);
                $this -> _workflow($cmd, $taskId);
            }
        }
        return RESULT['OK'];
    }
    // 提取下一页链接
    private function _getNextUrl($path, $html){
        $r = $this -> parser -> find(['url' => $path], $html);
        return $r['url'] ?? '';
    }

}
