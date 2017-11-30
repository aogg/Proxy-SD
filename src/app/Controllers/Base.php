<?php
/**
 * Created by PhpStorm.
 * User: code
 * Date: 2017/11/10
 * Time: 20:23
 */

namespace app\Controllers;


class Base extends \Server\CoreBase\Controller
{
    use \app\Traits\Debug;
    
    /**
     * @var null|\Server\Pack\IPack
     */
    protected $pack = null;
    
    
    protected $port = '';
    

    protected function getPack()
    {
        if ($this->pack === null) {
            // getPackFromFd经常获取不到
            $this->pack = $this->port !== '' ? get_instance()->portManager->getPack($this->port) :
                new \app\Pack\ProxyPack(); // 减少if判断
        }
        
        return $this->pack;
    }
    
}