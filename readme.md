# XXXcrawler爬虫微框架

XXXcrawler是一个用php编写的超轻量的微型爬虫框架，依赖 [Workerman](https://www.workerman.net/)进行定时调度。支持方便的自行扩展，包括下载及提取内容。

## 文件说明

- bootstrap.php 启动器
- Scheduler.php 调度器
- Parser.php 内容解释器
- Downloader.php 下载器
- Task.php  任务管理
- interface.php 自行扩展时的接口约束
- Filter.php 过滤器函数库
- Deliver.php 投递器函数包

- example 简单示例

  - main.php 入口
  - task.conf.php 缺省的任务配置

## 使用说明

下载XXXcrawler（如未有workerman，请先下载），修改task.conf.php的任务配置，使用php main.php start启动任务。

## 设计思路

### 工作流程

Scheduler通过workerman，启动一个timer和若干个worker。

timer负责定时查询任务列表，判断是否有需要执行的任务，如有，通过socket方式发送给worker异步处理。

worker接收到任务处理请求后，根据任务配置的指令，进行任务处理流程：

- 调用Downloader，下载目标页面。
- 调用Parser，根据配置在页面内容中提取需要的字段
- 如有需要，经过filter进行数据清洗
- 如有需要，调用deliver进行数据投递（如回显、写入文件、写入API或数据加等）
- 如有翻页，获取下一页链接，进入子任务
- 如有内页，进入内页子任务

worker处理完成后，发送消息通知timer进行状态更新。

### 扩展

目前只提供了一个最基本的功能框架，真正的爬虫还需要比较复杂的处理，比如登录、代理、反爬等。当前XXXcrawler可以对Downloader和Parser进行替换。只需要实现XXXcrawler\iDownloader和XXXcrawler\iParser接口，同时在入口中使用MAPPER进行映射即可，可参数示例的main.php

同样，XXXcrawler暂时只提供了用作示例的小量filter和deliver，使用者可以编写自己的filter和deliver函数。

## 指令说明

XXXcrawler通过数组来定义处理流程，以下一个基本例子：

```
    'data' => [
        'url'       => 'http://news.baidu.com/', // 入口地址
        'fields'    => [
            'title'  => 'xpath://div[@class="hotnews"]/ul/li', // 通过xpath获取内容到指定字段
            'url' => 'xxx'
        ],
        'sub' => [ // 内页子任务
            'url' => 'url', // 来自上级字段url
            'fields' => [
            ],
            'save' => [
            ]
        ],
        'save' => [ // 保存数据处理
            'filter' => [  // 指定过滤器
               // 'Merge' => []
            ],
            'deliver' => [
                'Console' => [], // 指定投递目标
            ]
        ],
        'next' => 'xpath://a[@class="nxt"]/@href' // 获取翻页连接
    ]
```

