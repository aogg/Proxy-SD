<?php

namespace app\Helper;


class HttpsProxy extends ProxyBase
{
    
    public $core_name = 'httpsProxy';
    
    
    
    public function proxySendTcp()
    {
        
        // 直接发送ssl加密内容，或者是http的黏包
        if($this->getHttpType() === \app\Controllers\ProxyController::PROXY_HTTPS_SEND){
            $this->sendHttp();
        }else{
            yield parent::proxySendTcp();
        }
    }
    
    
    
    public function clientOnConnect(\swoole_client $socket)
    {
        parent::clientOnConnect($socket);
        
        $type = $this->getHttpType();
        if ($type === \app\Controllers\ProxyController::PROXY_HTTPS_CONNECT) {
            //https成功connect，会再次
            $this->getPack()
                ->setFdsData($this->fd, 'client', $socket);
            
            // HTTP/1.1 200 \r\n Connection Established\r\n\r\n
            // HTTP/1.1 200 Connection Established\r\n\r\n
            $this->getController()->send("HTTP/1.1 200 \r\n \r\n\r\n"); // 可以不发送内容
        }else{
            // todo 这里应该不存在的
            // https的加密内容
            $socket->send($this->getClientData()->getClientDataStr());
        }
    }
    
    
    protected function checkTcpClient()
    {
        $hostData = yield parent::checkTcpClient();
        $type = $this->getHttpType();
        
        if ($hostData === false){
            return false;
        }
        
        if (
            $type === \app\Controllers\ProxyController::PROXY_HTTPS_CONNECT &&
            $this->getConfig('connectPortStrict', false) && $hostData['port'] !== 443
        ){ // 严格的443端口来进行connect
            return false;
        }
        
        return $hostData;
    }
    
    protected function sendHttp($sendBool = false)
    {
        return parent::sendHttp($this->getHttpType() === \app\Controllers\ProxyController::PROXY_HTTPS_SEND);
    }
    
    
}