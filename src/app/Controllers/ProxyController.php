<?php

namespace app\Controllers;
use Server\CoreBase\ChildProxy;


/**
 * telnet 127.0.0.1 9802
 *
 * @method $this getProxy()
 * @package app\Controllers
 */
class ProxyController extends Base
{
    
    const PROXY_HTTP = 1;
    const PROXY_HTTPS_CONNECT = 2;
    const PROXY_HTTPS_SEND = 3;
    const PROXY_HTTP_COMPACT = 4;
    
    
    /**
     * @var \app\Helper\HttpRequest|\GuzzleHttp\Psr7\Request
     */
    protected $client_data;
    
    /**
     * @var null|\app\Pack\ProxyPack
     */
    protected $pack = null;
    
    /**
     * http类型
     * 是否https代理
     *
     * @var bool
     */
    protected $httpType = 0;
    
    /**
     * 定义clientReceive是否发送后关闭
     *
     * @var bool
     */
    protected $receiveCloseBool = false;
    
    /**
     * \Swoole\Client的connect的超时时间
     *
     * @var int
     */
    protected $clientTimeout = 1;
    
    /**
     * @var null|\app\Helper\ProxyBase
     */
    protected $currentProxy = null;
    
    
//    public $core_name = 'ProxyController';


    
    
    public function __construct($proxy = ChildProxy::class)
    {
        parent::__construct($proxy);
        $this->port = $this->config->get('ports.proxy.socket_port');
    }
    
    
    public function tcp_proxy()
    {
        $this->dd(['fd' => $this->fd, 'worker_id' => get_instance()->server->worker_id,
            $this->client_data->getClientDataStr()], '');
        
        $this->init();
        yield $this->currentProxy->proxySendTcp();
    }
    
    
    protected function init()
    {
        $this->getHttpType();
        $this->currentProxy->checkClose();
        
    }
    
    

    
    
    
    
    public function http_test()
    {
        $this->send(333);
    }
    
    /**
     * 让tcp_onClose执行$this->destroy
     *
     * @param bool $autoDestroy
     */
    public function close($autoDestroy = false)
    {
        parent::close($autoDestroy);
    }
    
    public function send($data, $destroy = false)
    {
        parent::send($data, $destroy);
    }
    
    public function destroy()
    {
        $this->is_destroy = true;
        $this->dd('close', '');
        \app\Helper\ProxyBase::destroyFd($this->fd); // 清理数据
        $this->getPack()->delFdsData($this->fd); // close时可能会已经关闭pack
        $this->currentProxy = null;
        $this->proxy = null;
    
        parent::destroy();
    
        return;
        // gc 处理
        $gcConfig = $this->config->get('proxy.gcCollectCycles');
        if(
            (
                (!empty($gcConfig['time']) && !(time() % $gcConfig['time'])) ||
                (!empty($gcConfig['fd']) && !($this->fd % $gcConfig['fd'])) ||
                (!empty($gcConfig['num']) && $i > $gcConfig['num'] && $i = 1)
            )
            && gc_enable()
    
        ) {
            gc_collect_cycles();
        }
    }
    
    
    /**
     * close触发和receive触发都会创建controller
     */
    public function tcp_onClose()
    {
        // 清除
        $this->destroy();
    }
    
    public function tcp_onConnect()
    {
        $this->dd('connect');
    }
    
    public function __destruct()
    {
        // 每次都会调用，这样内容才会释放
//        var_dump(222);
    }
    
    
    /**
     * @return bool
     */
    protected function getHttpType()
    {
        $pack = $this->getPack();
        if (!$this->httpType){
            
            if ($pack->isSslFd($this->fd)){ // ssl发送
                $this->httpType = self::PROXY_HTTPS_SEND;
            }else if ($pack->isHttpFd($this->fd) && $this->client_data && !$this->client_data->isHttpBool()){ // http的黏包，黏包的数据只会触发一次connect
                $this->httpType = self::PROXY_HTTP_COMPACT;
            }else if ($this->client_data && $this->client_data->isHttpsBool()) { // ssl链接;     要将无头部请求放在前面
                $this->httpType = self::PROXY_HTTPS_CONNECT;
                $pack->setFdsData($this->fd, 'type', 'https');
            }else{ // http
                $this->httpType = self::PROXY_HTTP;
                $pack->setFdsData($this->fd, 'type', 'http');
            }
            
            
            if ($this->httpType === self::PROXY_HTTPS_SEND || $this->httpType === self::PROXY_HTTPS_CONNECT){
    
                $this->currentProxy = new \app\Helper\HttpsProxy($this->fd);
                
                // 导致黏包失败，connect时是4不是1
//                $this->currentProxy = \app\Helper\HttpsProxy::instance($this->fd);
            
            }else{
    
                $this->currentProxy = new \app\Helper\HttpProxy($this->fd);
//                $this->currentProxy = \app\Helper\HttpProxy::instance($this->fd);
            }
            
        }
    
        $this->addChild($this->currentProxy); // 不断刷新$this
//        $this->currentProxy->onAddChild($this);
        $this->currentProxy->setFdsData('clientData', $this->client_data)
            ->setFdsData('type', $this->httpType);
        
        if ($this->httpType === self::PROXY_HTTPS_CONNECT || $this->httpType === self::PROXY_HTTP){
            if ($pack->getFdsData($this->fd, 'request') === false && $this->client_data){
                $pack->setFdsData($this->fd, 'request', $this->client_data);
            }
        }
        
        return $this->httpType;
    }
    
    

    
}