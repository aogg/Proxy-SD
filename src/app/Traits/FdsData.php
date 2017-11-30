<?php

namespace app\Traits;


trait FdsData
{
    /**
     * @var array
     */
    protected $fdsData = [];
    
    
    public function getFdsData($fd, $key = '')
    {
        if (!isset($this->fdsData[$fd])) {
            return false;
        }
    
    
        if ($key === ''){
            return $this->fdsData[$fd];
        }else{
            return isset($this->fdsData[$fd][$key]) ? $this->fdsData[$fd][$key] : false;
        }
    }
    
    
    
    public function setFdsData($fd, $key, $value = null)
    {
        if (is_array($key)) {
            $this->fdsData[$fd] = $key;
        }else{
            $this->fdsData[$fd][$key] = $value;
        }
        
        return $this;
    }
    
    
    
    public function delFdsData($fd, $key = '')
    {
        if ($key === '') {
            unset($this->fdsData[$fd]);
        }else{
            unset($this->fdsData[$fd][$key]);
        }
        
        return $this;
    }
    
    
    public function countFds()
    {
        return count($this->fdsData);
    }
    
}