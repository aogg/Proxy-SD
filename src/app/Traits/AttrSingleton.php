<?php

namespace app\Traits;

/**
 * 单例类
 */
trait AttrSingleton
{
    protected $attrInstance = [];
    
    
    /**
     * 单例
     *
     * @return mixed
     */
    public function getAttrInstance($class = '', ...$params)
    {
        if (!isset($this->attrInstance[$class])) {
            $this->attrInstance[$class] = new $class(...$params);
        }
        
        return $this->attrInstance[$class];
    }
    
}