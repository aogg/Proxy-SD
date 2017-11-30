<?php

namespace app\Helper;


/**
 * 扩展\Swoole\Client
 * 如保存connect的host和port，用于在onError中显示
 *
 * @package app\Helper
 */
class SysClient extends \Swoole\Client
{
    
    protected $hostData = [];
    
    
    protected $timeout = 1;
    
    
    /**
     * @param string $key
     * @param string $default
     * @return array|string
     */
    public function getHostData($key = '', $default = '')
    {
        if (empty($key)) {
            return $this->hostData;
        }
        
        return isset($this->hostData[$key]) ? $this->hostData[$key] : $default;
    }
    
    /**
     * @param mixed $hostData
     * @param mixed  $value
     */
    public function setHostData($hostData, $value = null)
    {
        if (is_array($hostData)) {
            $this->hostData = $hostData;
        }else{
            $this->hostData[$hostData] = $value;
        }
    }
    
    
    public function connect($host = '', $port = 0, $timeout = null, $flag = 0)
    {
        if (empty($host) && !empty($this->hostData)) {
            $host = $this->getHostData('ip', $host);
            $port = $this->getHostData('port', $port);
        }
    
        // 国外服务器比较慢
        if (is_null($timeout)) {
            $timeout = $this->timeout;
        }
        
        return parent::connect($host, $port, $timeout, $flag);
    }
    
    /**
     * @param int $timeout
     * @return SysClient
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        
        return $this;
    }
    
    
}