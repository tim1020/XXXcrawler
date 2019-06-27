<?php
// 测试下载及提取
require __DIR__.'/../interface.php';
require __DIR__.'/../Downloader.php';
require __DIR__.'/../Parser.php';

use XXXcrawler\Downloader;
use XXXcrawler\Parser;

$url = 'http://news.baidu.com/';
$fields = [
   'title' => 'xpath://title'
];

$downloader = new Downloader();
$parser = new Parser();
$html = $downloader -> fetch($url);
$data = $parser -> find($fields, $html);

print_r($data);