<?php

namespace app\Helper;


/**
 * 重写\GuzzleHttp\Psr7\parse_request
 *
 * @package app\Helper
 */
class HttpRequest extends HttpMessage
{
    
    
    
    protected $httpsBool = false;
    
    
    protected $httpBool = false;
    
    
    protected $connectBool = false;
    
    
    /**
     * @var null|\GuzzleHttp\Psr7\Request
     */
    protected $message = null;
    
    
    protected $hostData = null;
    
    
    protected $defaultPort = 80;
    
    protected function getInitCompactStr()
    {
        return $this->getClientDataStrByHttp();
    }
    
    
    
    
    protected function initUseTry()
    {
        if (!is_null($this->message)){
            return;
        }
    
        $data = \GuzzleHttp\Psr7\_parse_message($this->clientDataStr);
        $matches = [];
        $parts = explode(' ', $data['start-line'], 3);
        $method = $parts[0];
        $uri = isset($parts[1]) ? $parts[1] : '';
        $version = isset($parts[2]) ? $this->handleVersion($parts[2]) : '1.1';
        $body = $data['body'];
    
        if ($method === "CONNECT"){ // https请求
            $this->connectBool = true;
            $this->httpsBool = true;
        }else if (!preg_match('/^[\S]+\s+([a-zA-Z]+:\/\/|\/).*/', $data['start-line'], $matches)) { // 没有http头
            $method = $uri = $version = '';
            $body = $data['start-line'];
        }else{
            $this->httpBool = true;
        }
    
    
        $request = new \GuzzleHttp\Psr7\Request(
            $method,
            isset($matches[1]) && $matches[1] === '/' ? \GuzzleHttp\Psr7\_parse_request_uri($uri, $data['headers']) : $uri,
            $data['headers'],
            $body,
            $version
        );
        
        
        $this->addAcceptBodyLength(strlen($this->clientDataStr));
    
    
        // withRequestTarget是检测url是否有空格
        $this->message = isset($matches[1]) && $matches[1] === '/' ? $request : $request->withRequestTarget($uri);
    }
    
    
    /**
     * 获取版本
     *
     * @param $version
     * @return string
     */
    protected function handleVersion($version)
    {
        if (empty($version)) {
            return '1.1';
        }
        
        $arr = explode('/', $version);
    
        return isset($arr[1]) ? $arr[1] : '1.1';
    }

    
    
    
    /**
     * @return array|bool|null
     */
    public function getHostData()
    {
        if (!is_null($this->hostData)){
            return $this->hostData;
        }
        $this->hostData = false;
        
        // 可修改成$this->request->uri获取的
        $data = $this->getHostLine();
        if (!empty($data)) {
            $data = explode(':', $data, 2);
            $ipData = yield (new \app\Helper\Host())->getHost($data[0]);
    
            if (is_array($ipData) && $ipData['ip'] !== false){
                $this->hostData = [
                    'host' => $data[0],
                    'ip' => $ipData['ip'],
                    'port' => empty($data[1]) ? $this->defaultPort : $data[1],
                    'ipBool' => $ipData['ipBool'],
                ];
            }
        }
    
        return $this->hostData;
    }
    
    
    
    protected function getHostLine()
    {
        // connect时可能在host中没有写明端口（如app）
        if ($this->isConnectBool()) {
            /** @var \GuzzleHttp\Psr7\Request $request */
            $request = $this->getMessage();
            return ltrim($request->getUri(), '//'); // 去除自动补的//
        }
        
        return $this->getHeaderLine('host');
    }
    
    
    
    /**
     * 直接获取getHostData的返回值
     *
     * @return array|bool|null
     */
    public function getHostDataReturn()
    {
        /** @var \Generator $generator */
        $generator = $this->getHostData();
    
        foreach ($generator as $item) {
            $a = 1; // ide提示
        }
        
        return $generator->getReturn();
    }
    

    
    
    /**
     * 处理http的header头
     *
     * @return mixed
     */
    public function getClientDataStrByHttp()
    {
        return preg_replace([
            '/Proxy-Connection/'
        ], [
            'connection'
        ], $this->clientDataStr);
    }
    
    
    /**
     * @return bool
     */
    public function isHttpsBool()
    {
        $this->init();
        return $this->httpsBool;
    }
    

    
    /**
     * @return bool
     */
    public function isHttpBool()
    {
        $this->init();
        return $this->httpBool;
    }
    
    
    /**
     * @return bool
     */
    public function isConnectBool()
    {
        $this->init();
        return $this->connectBool;
    }
    
    
}