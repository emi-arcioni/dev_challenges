<?php
declare(strict_types=1);

namespace App;

use Redis;
use RedisException;

class Database 
{
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
        $this->redis->connect('redis', 6379);
    }

    public function __get($prop)
    {
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
    }
}