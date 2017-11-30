<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-7-14
 * Time: 下午1:58
 */

use Server\CoreBase\PortManager;

$config['ports']['proxy'] = [
    'socket_type' => PortManager::SOCK_TCP,
    'socket_name' => '0.0.0.0',
    'socket_port' => 9802,
    'pack_tool' => 'ProxyPack',
    'route_tool' => 'ProxyRoute',
    'middlewares' => ['MonitorMiddleware']
];

//$config['ports'][] = [
//    'socket_type' => PortManager::SOCK_TCP,
//    'socket_name' => '0.0.0.0',
//    'socket_port' => 1883,
//    'pack_tool' => 'MqttPack',
//    'route_tool' => 'NormalRoute',
//    'middlewares' => ['MonitorMiddleware']
//];

$config['ports']['http'] = [
    'socket_type' => PortManager::SOCK_HTTP,
    'socket_name' => '0.0.0.0',
    'socket_port' => 9803,
    'route_tool' => 'NormalRoute',
    'middlewares' => ['MonitorMiddleware', 'NormalHttpMiddleware']
];

$config['ports']['webSocket'] = [
    'socket_type' => PortManager::SOCK_WS,
    'socket_name' => '0.0.0.0',
    'socket_port' => 8083,
    'route_tool' => 'NormalRoute',
    'pack_tool' => 'NonJsonPack',
    'opcode' => PortManager::WEBSOCKET_OPCODE_TEXT,
    'middlewares' => ['MonitorMiddleware']
];

return $config;