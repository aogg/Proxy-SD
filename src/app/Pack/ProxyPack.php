<?php

namespace app\Pack;


class ProxyPack extends \Server\Pack\LenJsonPack
{
    
    use \app\Traits\FdsData;
    
    public function __construct()
    {
    
    }
    
    
    public function getProbufSet()
    {
        return [
                // 多个协议，就需要重新设置tcp
                'open_length_check' => false,
            
                // 会导致无法发送post大数据
//                'open_eof_check'=> true,
//                'package_eof' => "\r\n",
            ] + parent::getProbufSet();
    }
    

    
    
    public function isHttpFd($fd)
    {
        return $this->getFdsData($fd, 'type') === 'http';
    }
    
    
    public function isSslFd($fd)
    {
        return $this->getFdsData($fd, 'type') === 'https';
    }
    
    
    
    
    /**
     * 数据包编码
     * @param $buffer
     * @return string
     */
    public function encode($buffer)
    {
        return $buffer;
    }
    
    /**
     * @param $buffer
     * @return string
     */
    public function decode($buffer)
    {
        return $buffer;
    }
    
    
    public function pack($data, $topic = null)
    {
        return $data;
    }
    
    

    
    
    public function unPack($data)
    {
//        throw new \Server\CoreBase\SwooleException('Proxy unPack 失败');
        
//        return new \app\Helper\TcpClientData($this->decode($data));
    
        $request = new \app\Helper\HttpRequest($this->decode($data));
        
        return $request;
    }
    
    
    public function errorHandle($e, $fd)
    {
        get_instance()->close($fd);
    }
    
}