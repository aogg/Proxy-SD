<?php
/**
 * Created by PhpStorm.
 * User: code
 * Date: 2017/11/14
 * Time: 16:24
 */



$config['proxy']['dns'] = 2; // 2为DNS随机，可ip指定dns服务器
$config['proxy']['gcCollectCycles'] = [ // 手动执行gc的配置
    'time' => 30,
    'fd' => 20,
    'num' => 100,
];
$config['proxy']['connectPortStrict'] = false; // 严格的443端口来进行connect
$config['proxy']['consoleDetail'] = false; // 输出详细的调试数据
$config['proxy']['clientTimeout'] = 20; // 代理\Swoole\Client的connect超时时间
$config['proxy']['clientTimeoutAgainAddTime'] = 0; // 代理\Swoole\Client的connect超时二次重试的增加时间
$config['proxy']['hostMap'] = [ // host映射
//    'www.baidu.com' => '115.239.210.27',
];
$config['proxy']['ipMap'] = [ // ip映射
    '127.0.0.1' => '192.168.5.7',
    '172.17.0.2' => false, // false为直接使用原有host
];


return $config;