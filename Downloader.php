<?php
namespace XXXcrawler;

/**
 * 页面下载器，返回页面的html内容
 * 
 * 负责下载指定目标的页面内容，必要时自动加载“登录器”、“代理池”、“js解释器”等
 */

class Downloader implements iDownloader {
    // 爬取网页内容，这里是简单实现，功能完善的下载器应该包括: 伪装，代理、控频、登录、无头浏览器(js解释)等
    /**
     * @param mixed $urls [url1,url2] || url
     */
    public function fetch($urls) {
        if(!is_array($urls)) return $this -> _fetchOne($urls);
        $mh = curl_multi_init(); 
        $curl_array = array(); 
        foreach($urls as $i => $url) { 
            $curl_array[$i] = curl_init($url); 
            $this -> _setOpt($curl_array[$i]);
            curl_multi_add_handle($mh, $curl_array[$i]); 
        } 
        $running = NULL; 
        do { 
            usleep(5000); 
            curl_multi_exec($mh, $running); 
        } while($running > 0); 
        
        $res = array(); 
        foreach($urls as $i => $url) { 
            $res[$i] = curl_multi_getcontent($curl_array[$i]); 
            curl_multi_remove_handle($mh, $curl_array[$i]); 
        } 
        curl_multi_close($mh);        
        return $res; 
    }

    //下载单个Url
    private function _fetchOne($url) {
        $ch = curl_init($url); 
        $this -> _setOpt($ch);
        if( ! $result = curl_exec($ch)) { 
            return false;
        } 
        curl_close($ch); 
        return $result; 
    }

    private function _setOpt($ch, $options=[]) {
        $defaults = array( 
            CURLOPT_HEADER => 0, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => 10 ,
            CURLOPT_USERAGENT => "Mozilla/5.0",
        ); 
        
        curl_setopt_array($ch, ($options + $defaults)); 
    }
}