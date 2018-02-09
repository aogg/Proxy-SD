<?php

namespace app\Helper;


class GC
{
    protected static $config = null;
    
    protected static $num = 0;
    
    protected static $gcTime = 0;
    
    protected static $initBool = false;
    
    
    
    public static function start()
    {
        if (self::startBool()) {
            gc_collect_cycles();
        }
    }
    
    
    
    
    protected static function init()
    {
        if (!self::$initBool) {
            self::$initBool = true;
            
            self::$gcTime = time();
        }
    }
    
    protected static function getConfig()
    {
        if (is_null(self::$config)) {
            self::$config = get_instance()->config->get('proxy.gcCollectCycles', []);
        }
        
        return self::$config;
    }
    
    
    protected static function startBool()
    {
        self::init();
        $config = self::getConfig();
        if (!empty($config['time'])){
            if (!((time() - self::$gcTime) % $config['time'])){
                return true;
            }
        }
        
        if (!empty($config['maxNum'])){
            ++self::$gcTime;
            if (self::$gcTime > $config['maxNum']){
                self::$gcTime = 1;
                return true;
            }
        }
        
        return false;
    }

    
}