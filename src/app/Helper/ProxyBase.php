<?php

namespace app\Helper;


abstract class ProxyBase extends \Server\CoreBase\Child
{
    use \app\Traits\FdsData,
        \app\Traits\Debug
    {
        getFdsData as getFdData;
        setFdsData as setFdData;
        delFdsData as delFdData;
    }
    
    

    
    
    protected $fd = 0;
    
    protected static $instance = null;
    
    
    
    protected $clientTimeout = 1;
    
    /**
     * @var \app\Controllers\ProxyController
     */
    public $parent;
    
    protected $receiveCloseBool = false;
    
    /**
     * @var null|\app\Pack\ProxyPack
     */
    protected $pack = null;
    
    
    
    protected $sendClose = false;
    
    
    /**
     * 单例
     *
     * @return static
     */
    public static function instance($fd)
    {
        if (!isset(self::$instance[$fd])){
            self::$instance[$fd] = new static($fd);
        }
        
        return self::$instance[$fd];
    }
    
    
    /**
     * ProxyBase constructor.
     */
    public function __construct($fd)
    {
        $this->fd = $fd;
        
        $this->clientTimeout = $this->getConfig('proxy.clientTimeout', $this->clientTimeout);
        
        parent::__construct();
    }
    
    
    /**
     * @return \app\Controllers\ProxyController
     */
    protected function getController()
    {
        return $this->parent->getProxy();
    }
    
    
    public static function destroyFd($fd)
    {
        unset(self::$instance[$fd]);
    }
    
    
    public function destroy()
    {
        static::destroyFd($this->fd);
        parent::destroy();
        $this->proxy = null;
    }
    
    public function getFdsData($key = '')
    {
        return $this->getFdData($this->fd, $key);
    }
    
    public function setFdsData($key, $value = null)
    {
        return $this->setFdData($this->fd, $key, $value);
    }
    
    public function delFdsData($key = '')
    {
        return $this->setFdsData($this->fd, $key);
    }
    
    protected function getConfig($key, $default)
    {
        return get_instance()->config->get($key, $default);
    }
    
    
    
    
    
    protected function getHttpType()
    {
        return $this->getFdsData('type');
    }
    
    /**
     * @return bool|\app\Helper\HttpRequest|\GuzzleHttp\Psr7\Request
     */
    protected function getClientData()
    {
        return $this->getFdsData('clientData');
    }
    
    
    
    
    
    
    public function proxySendTcp()
    {
        // connect中发送
        $noClose = yield $this->getTcpClient();
    
        if ($this->sendClose || !$noClose){
            $this->dd(['触发close' => $this->getClientData()->getClientDataStr()], '');
            $this->getController()->close();
        }
    }
    
    
    
    protected function checkTcpClient()
    {
    
        $hostData = yield $this->getClientData()->getHostData();
        if ($hostData === false || empty($hostData['ip'])){ // 暂时无法解析到ip，有时间缓存
            return false;
        }
        
        return $hostData;
    }
    
    
    protected function getTcpClient()
    {
        $hostData = yield $this->checkTcpClient();
        if (false === $hostData) {
            return false;
        }
        
        $client = new \app\Helper\SysClient(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $client->on('connect', [$this, 'clientOnConnect']);
        $client->on('error', [$this, 'clientOnError']);
        $client->on('close', [$this, 'clientOnClose']);
        $client->on('receive', [$this, 'clientOnReceive']);
        
        
        $client->setHostData($hostData);
        $client->setTimeout($this->clientTimeout);
        
        if ($this->clientRunConnect($client) === false){
            return false;
        }
        
        return $client;
    }
    
    
    /**
     * @param \app\Helper\SysClient $client
     * @return bool
     */
    protected function clientRunConnect($client)
    {
        $bool = false;
        
        try{
            $bool = $client->connect();
        }catch (\Exception $exception){ // 会报错
            $this->dd(['clientRunConnect 异常' => $exception], 'debug');
        }
        
        if (!$bool) {
            $this->dd([$this->getClientData()->getHostDataReturn(), 'client connect失败'], 'debug');
            $client->close(true); // 不为true会报错
            $this->getController()->close();
            
            return false;
        }
        
        return true;
    }
    
    
    
    
    public function clientOnConnect(\swoole_client $socket)
    {
        // self::PROXY_HTTP_COMPACT不会触发这个函数
        $this->dd('client成功connect', '');
    }
    
    
    public function clientOnClose(\swoole_client $socket)
    {
        $this->getController()->destroy();
        $this->dd('client close', '');
    }
    
    
    /**
     * 客户端连接错误回调
     *
     * @param \swoole_client $socket
     */
    public function clientOnError(\swoole_client $socket)
    {
        /** @var \app\Helper\SysClient $socket */
        $this->dd(['client error', '错误码' => $socket->errCode, $socket->getHostData(),
            $this->getClientData()->getClientDataStr()], '');
        
        // 重试自增时间
        $addTime = $this->getConfig('proxy.clientTimeoutAgainAddTime', 0);
        if ($addTime && !$socket->getHostData('reconnect', false)) {
            $socket->setHostData('reconnect', true); // 是否已重试
            $timeout = $this->clientTimeout + $addTime;
            $socket->setTimeout($timeout);
            
            
            $this->dd(['超时重试' => $socket->getHostData(), '时间' => $timeout], 'debug');
            
            // 重试
            $this->clientRunConnect($socket);
            return;
        }
        
        if ($socket->isConnected()) {
            $socket->close();
        }
        $this->getController()->close();
    }
    
    
    public function clientOnReceive(\swoole_client $socket, $data)
    {
        $this->dd($this->fd, '');
        $this->dd($data, '');
        $this->getController()->send($data, $this->receiveCloseBool);
        if ($this->receiveCloseBool){ // 可用bufferEmpty
            $socket->close();
        }
    }
    
    public function checkClose()
    {
    }
    
    
    /**
     * 发送http的黏包
     */
    protected function sendHttp($sendBool = false)
    {
        /** @var \app\Helper\HttpRequest|\GuzzleHttp\Psr7\Request $request */
        $request = $this->getPack()->getFdsData($this->fd, 'request');
        
        if (!$request || !method_exists($request, 'isSend')){
            return false;
        }
    
        $bool = false;
        /** @var \app\Helper\SysClient $client */
        $client = $this->getPack()->getFdsData($this->fd, 'client');
        if (!$client){
            $this->destroy();
        }else if (!$client->isConnected()){
            $this->destroy();
        }else if ($sendBool){ // ssl
            $client->send($this->getClientData()->getClientDataStr());
            $this->destroy();
        }else if (!$request->isSend()){
            $this->destroy();
        }else{
            $bool = true;
        }
        
        if ($bool){
//        $client->send($request->getClientDataStr());
            $client->send($request->getAll());
            $this->getPack()->delFdsData($this->fd, 'request'); // 清空数据
        }
        
        return $bool;
    }
    
    
    /**
     * @return \app\Pack\ProxyPack
     */
    protected function getPack()
    {
        if (is_null($this->pack)) {
            $this->pack = get_instance()->portManager->getPackFromFd($this->fd) ?: new \app\Pack\ProxyPack(); // 减少if判断
        }
        
        return $this->pack;
    }
    
    public function __destruct()
    {
//        var_dump(111);
        static::destroyFd($this->fd);
    }
    
    
}