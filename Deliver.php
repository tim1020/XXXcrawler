<?php

/**
 * 投递处理
 * 
 * 以函数包方式提供，负责将爬取的数据结果投递到指定目标，可定义多个。
 * 规则为： 以deliverXxxx命名，输入为要投递的数据(json格式)，输出为是否成功
 */

// 回显
function deliverConsole($data) {
    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    plog("Deliver: %s", $data);
    return true;
}

// 保存为文件
function deliverFile($data, $fname) {
    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    $r = file_put_contents($fname, $data."\n", FILE_APPEND);
    return true;
}

// 发送到api
function deliverApi($data, $api, $method) {

}