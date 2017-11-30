<?php

namespace app\Route;


class ProxyRoute implements \Server\Route\IRoute
{
    protected $clientData = null;
    
    function handleClientData($data)
    {
        $this->clientData = $data;
        
        return $data;
    }
    
    function handleClientRequest($request)
    {
    
    }
    
    function getControllerName()
    {
        return 'ProxyController';
    }
    
    function getMethodName()
    {
        return 'proxy';
    }
    
    function getParams()
    {
        return null; // 暂时没有
    }
    
    function getPath()
    {
        return '';
    }
    
    function errorHandle(\Exception $e, $fd)
    {
        get_instance()->send($fd, "Error:" . $e->getMessage(), true);
        get_instance()->close($fd);
    }
    
    function errorHttpHandle(\Exception $e, $request, $response)
    {
        // 不存在的
    }
    
    
}