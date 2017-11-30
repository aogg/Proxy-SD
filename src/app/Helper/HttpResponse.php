<?php

namespace app\Helper;


class HttpResponse extends HttpMessage
{
    protected function initUseTry()
    {
        if (!is_null($this->message)){
            return;
        }
    
    
    
        $data = \GuzzleHttp\Psr7\_parse_message($this->message);
        // According to https://tools.ietf.org/html/rfc7230#section-3.1.2 the space
        // between status-code and reason-phrase is required. But browsers accept
        // responses without space and reason as well.
        if (!preg_match('/^HTTP\/.* [0-9]{3}( .*|$)/', $data['start-line'])) {
            throw new \InvalidArgumentException('Invalid response string');
        }
        $parts = explode(' ', $data['start-line'], 3);
    
        $this->message = new \GuzzleHttp\Psr7\Response(
            $parts[1],
            $data['headers'],
            $data['body'],
            explode('/', $parts[0])[1],
            isset($parts[2]) ? $parts[2] : null
        );
        
        
    }
    
    protected function getInitCompactStr()
    {
        return $this->getClientDataStr();
    }
    
    
}