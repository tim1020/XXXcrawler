<?php
// 任务配置
return [
    ['id' => 1, 'intval' => 0, 'data' => [
        'url'       => 'http://news.baidu.com/',
        'fields'    => [
            'title'  => 'xpath://div[@class="hotnews"]/ul/li',
            'url' => 'xxx'
        ],
        // 'sub' => [
        //     'url' => 'url', // 来自上级字段
        //     'fields' => [
        //     ],
        //     'save' => [
        //     ]
        // ],
        'save' => [
            'filter' => [
               // 'Merge' => []
            ],
            'deliver' => [
                'Console' => [],
                //'File'    => ['/tmp/list']
            ]
        ],
       // 'next' => 'xpath://a[@class="nxt"]/@href'
    ]]
];
