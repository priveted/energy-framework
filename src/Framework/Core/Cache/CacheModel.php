<?php

namespace Energy\Core\Cache;

class CacheModel
{


    /** 
     * Cache key
     * @var string
     */

    public $key;


    /** 
     * Content cache data
     * @var mixed
     */

    public $data;

    
    /** 
     * @param int - Expiration timestamp
     * @var int
     */

    public $expire = 0;


    /** 
     * @param string - Data Type / Subdirectory
     * @var string
     */

    public $dataType = 'data/html';


    /** 
     * Get cache data
     */

    public function get(): mixed
    {
        return ($this->dataType === 'data/html' && !is_array($this->data)) ? stripcslashes($this->data) : $this->data;
    }


    /** 
     * Get the expiration time
     */

    public function expire(): int
    {
        return $this->expire;
    }


    /** 
     * Has the time expired
     */

    public function isTimeExpired(): bool
    {
        return ($this->expire && $this->expire <= time());
    }
}
