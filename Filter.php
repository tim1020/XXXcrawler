<?php
/**
 * 过滤器，以函数包方式提供。负责对提取出来的数据进行进一步的清选处理
 * 
 * 规则：以filterXxxx方式命名.第一参数为输入数据，其它为可选参数，输出为处理后的结果数据
 */

// 下载并替换图片内容
function filterImgDownload($data, $field, $path) {
    $name = basename($data[$field]);
    $img = file_get_contents($data[$field]);
    file_put_contents($path.$name, $img);
    $data[$field] = $name;
    return $data;
}
// 合并字段
function filterMerge($data) {

    $keys = array_keys($data);
    $re = [];
    foreach($data[$keys[0]] as $k => $v) {
        $row = [];
        foreach($keys as $kv) {
            if(strpos($kv, '_') === 0) continue;
            $row[$kv] = $data[$kv][$k];
        }
        $re[] = $row;
    }
    return $re;
}
