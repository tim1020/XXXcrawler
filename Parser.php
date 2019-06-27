<?php
namespace XXXcrawler;

/**
 * 页面内容提取
 */

class Parser implements iParser {
    public function find($fields, $html) {
        $re = [];
        $dom = new \DOMDocument();
        @$dom -> loadHTML($html);
        $dom -> normalize();
        $xpath = new \DOMXPath($dom);
        foreach($fields as $k => $v) {
            list($type, $path) = explode(":", $v);
            switch(strtolower(trim($type))) {
                case 'xpath':
                    $r = $this -> _getByXpath($path, $xpath);
                    if($r) $re[$k] = $r;
                    break;

            }
        }
        return $re;
    }
    // 用xpath获取
    private function _getByXpath($path, $xpath) {
        $re = [];
        $nodes = $xpath -> query($path);
        // foreach ($nodes as $val) {
        //     $item = $val -> childNodes;
        //     foreach ($item as $v) {
        //         $re[] = trim($v -> textContent);
        //     }
        // }
        if($nodes -> length == 1) {
            $re = $nodes -> item(0) -> nodeValue;
        } else {
            for ($i = 0; $i < $nodes -> length; $i++) {
                $item = $nodes -> item($i);
                $re[] = $item -> nodeValue;
            }
        }
        return $re;
    }
}