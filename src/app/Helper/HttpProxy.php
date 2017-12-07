<?php

namespace app\Helper;


class HttpProxy extends ProxyBase
{
    
    public $core_name = 'httpProxy';
    
    
    
    public function clientOnConnect(\swoole_client $socket)
    {
        parent::clientOnConnect($socket);
    
    
        if ($this->getHttpType() === \app\Controllers\ProxyController::PROXY_HTTP) {
            // 用于黏包
            $this->getPack()
                ->setFdsData($this->fd, 'client', $socket);
        
            $this->sendHttp();
        
        }
    }
    
    
    public function proxySendTcp()
    {
        // 直接发送ssl加密内容，或者是http的黏包
        if ($this->getHttpType() === \app\Controllers\ProxyController::PROXY_HTTP_COMPACT) { // http黏包
            /** @var \app\Helper\HttpRequest|\GuzzleHttp\Psr7\Request $request */
            $request = $this->getPack()->getFdsData($this->fd, 'request');
            if ($request){
                $request->pushCompact($this->getClientData());
            }
            $this->sendHttp();
        }else{
            yield parent::proxySendTcp();
        }

    }
    
    
    
    protected function checkTcpClient()
    {
        $type = $this->getHttpType();
    
    
        if ($type === \app\Controllers\ProxyController::PROXY_HTTP && !$this->getClientData()->hasHeader('host')) { // 正常http没有host头
            return false;
        }

        
        return yield parent::checkTcpClient();
    }
    
    
    
    public function checkClose()
    {
        if ($this->getHttpType() === \app\Controllers\ProxyController::PROXY_HTTP){
            $connection = $this->getClientData()->getHeader('Proxy-Connection');
            // http，浏览器也会复用请求
            $this->receiveCloseBool = !$connection === 'keep-alive';
        }
        
        parent::checkClose();
    }
    
}