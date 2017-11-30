<?php

namespace app\Helper;


class TcpClientData
{
    
    protected $tcpClientData = '';
    
    
    protected $clientDataArr = [];
    
    
    /**
     * TcpClientData constructor.
     */
    public function __construct($clientData)
    {
        $this->tcpClientData = $clientData;
        $this->handleClientDataArr();
    }
    
    
    
    /**
     * 获取指定的key
     *
     * @param        $key
     * @param string $default
     * @return mixed|string
     */
    public function getData($key, $default = '')
    {
        if (!isset($this->clientDataArr[$key])) {
            return $default;
        }
        
        return $this->clientDataArr[$key];
    }
    
    
    protected function handleClientDataArr()
    {
        if (empty($this->tcpClientData)) {
            return;
        }

        $value = explode("\r\n", $this->tcpClientData);
        $result = [];
        foreach ($value as $item) {
            $temp = explode(':', $item, 2);
            if (!empty($temp[0]) && !empty($temp[1])) {
                $result[strtolower(trim($temp[0]))] = ltrim($temp[1]);
            }
        }
    
        $this->clientDataArr = $result;
    }
    
    /**
     * @return string
     */
    public function getTcpClientData()
    {
        return $this->tcpClientData;
    }
    
    public function __toString()
    {
        return $this->getTcpClientData();
    }
    
    
}