<?php

namespace app\Traits;


trait Debug
{
    
    /**
     * @var null|array
     */
    protected $log = null;
    
    protected function dd($data, $name = 'console')
    {
        if ($name === 'console' || $name === '') {
            get_instance()->config->get('consoleDetail', false) &&
            $this->getLog($name)->addDebug(is_scalar($data)?$data:var_export($data, true));
        }else if ($name === 'debug'){
            var_dump($data);
        }else if ($name){
            $this->getLog($name)->addDebug(is_scalar($data)?$data:var_export($data, true));
        }
        
        return 1;
    }
    
    
    protected function ddBit(...$args)
    {
        $this->getLog('debug-bit')->debug(...$args);
    }
    
    
    protected function ddBacktrace($limit = 3)
    {
        $this->dd(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit), 'debug');
    }
    
    
    
    /**
     * @param $name
     * @return \Monolog\Logger
     */
    protected function getLog($name)
    {
        if (!isset($this->log[$name])) {
            $this->log[$name] = $logger = new \Monolog\Logger($name);
            
            if ($name === 'debug'){
                $logger->pushHandler(new \Monolog\Handler\PHPConsoleHandler());
            }else if ($name === 'debug-bit'){
                $logger->pushHandler(new \Monolog\Handler\RotatingFileHandler(LOG_DIR . '/debug.log'));
            }
        }
        
        return $this->log[$name];
    }
    
    
}