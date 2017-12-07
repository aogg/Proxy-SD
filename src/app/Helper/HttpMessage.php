<?php
/**
 * Created by PhpStorm.
 * User: code
 * Date: 2017/11/20
 * Time: 17:04
 */

namespace app\Helper;

/**
 * Class HttpMessage
 *
 * @method string getHeaderLine($header)
 * @package app\Helper
 */
abstract class HttpMessage
{
    
    protected $clientDataStr = '';
    
    
    /**
     * 已接收长度
     *
     * @var int
     */
    protected $acceptBodyLength = 0;
    
    
    /**
     * @var null|\SplQueue|$this[]
     */
    protected $httpCompact = null;
    
    /**
     * @var null|\GuzzleHttp\Psr7\MessageTrait|\Psr\Http\Message\MessageInterface
     */
    protected $message = null;
    
    
    
    protected $sendOwnBool = false;

    
    
    abstract protected function getInitCompactStr();
    abstract protected function initUseTry();
    
    
    public function __construct($clientData)
    {
        $this->clientDataStr = $clientData;
    }
    
    
    protected function init()
    {
        try{
            $this->initUseTry(); // 不报错
        }catch (\Exception $exception){
            var_dump([__CLASS__, '报错', $exception->getMessage()]);
            get_instance()->log->warning(__METHOD__ . ': ' . $exception->getMessage());
        }
    }
    
    
    public function getMessage()
    {
        $this->init();
        return $this->message;
    }
    
    
    public function addAcceptBodyLength($length)
    {
        $this->acceptBodyLength += $length;
        
        return $this;
    }
    
    
    /**
     * @return int
     */
    public function getAcceptBodyLength()
    {
        $this->init();
        
        return $this->acceptBodyLength;
    }
    
    
    public function isSend()
    {
        $length = $this->getContentLength();
        if ($length){
            return $length <= $this->getAcceptBodyLength();
        }else if($encoding = $this->getHeaderLine('Transfer-Encoding')){
            return $encoding === 'chunked';
        }else{
            return true;
        }
    }
    
    
    /**
     * @return string
     */
    public function getClientDataStr()
    {
        return $this->clientDataStr;
    }
    
    
    public function getContentLength()
    {
        $this->init();
        
        return intval($this->getHeaderLine('Content-Length'));
    }
    
    
    
    /**
     * 保存黏包
     * 不能分批次发送
     *
     * @param self $own
     * @return $this
     */
    public function pushCompact(self $own)
    {
        if (is_null($this->httpCompact)) {
            $this->httpCompact = new \SplQueue();
        }
    
        $this->httpCompact->push($own);
        $this->addAcceptBodyLength(strlen($own->getClientDataStr()));
        
        return $this;
    }
    
    
    public function getAll()
    {
        $str = '';
        
        if (!$this->sendOwnBool){ // 不push自身
            $str .= $this->getClientDataStr();
            $this->sendOwnBool = true;
        }
        
        if (!is_null($this->httpCompact)){
            while (!$this->httpCompact->isEmpty()){
                $str .= $this->httpCompact->shift()->getClientDataStr();
            }
        }
        
        return $str;
    }
    
    public function __destruct()
    {
//        var_dump('aaa');
    }
    
    
    public function __get($name)
    {
        $this->init();
        return $this->message->$name;
    }
    
    public function __set($name, $value)
    {
        $this->init();
        $this->message->$name = $value;
    }
    
    
    /**
     *
     * @param $name
     * @param $arguments
     * @return \GuzzleHttp\Psr7\Request
     */
    public function __call($name, $arguments)
    {
        $this->init();
        return $this->message->$name(...$arguments);
    }
    
    

    
    
}