<?php
declare(strict_types=1);
/**
 * Author: Weida
 * Date: 2023/11/5 14:10
 * Email: sgenmi@gmail.com
 */

namespace Weida\JinritemaiCore\Cache;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

class RedisAdapter implements CacheInterface
{

    private $redis;
    private string $prefix='';
    public function __construct($redis,$prefix=""){
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    public function get(string $key, $default = null): mixed
    {
        $redis = $this->getRedis();
        $val = $redis->get($this->getCompleteKey($key));
        return $val ?unserialize($val):$default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $redis = $this->getRedis();
        $key = $this->getCompleteKey($key);
        $redis->sadd($this->getCompleteKey('s:all:jinritemai:cache'), $key);
        $value = serialize($value);
        return $redis->set($key,$value,$ttl);
    }

    public function delete(string $key): bool
    {
        $redis = $this->getRedis();
        return boolval($redis->del($this->getCompleteKey($key)));
    }

    public function clear(): bool
    {
        $redis = $this->getRedis();
        $keys = $redis->sMembers($this->getCompleteKey('s:all:jinritemai:cache'));
        foreach ($keys as $key) {
            $this->delete(strval($key));
        }
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given.', get_debug_type($keys)));
        }
        $keys = array_map(function ($v){
            return $this->getCompleteKey(strval($v));
        },$keys);
        $redis = $this->getRedis();
        $val = $redis->mget($keys);
        $ret=[];
        foreach ($keys as $k=>$v){
            $_v = $default;
            if(isset($val[$k])){
                $_v = unserialize($val[$k]);
            }
            $ret[$v]=$_v;
        }
        return $ret;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new InvalidArgumentException(sprintf('Cache values must be array or Traversable, "%s" given.', get_debug_type($values)));
        }
        foreach ($values as $key => $value) {
            $this->set($key, $values, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!is_array($keys)) {
            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given.', get_debug_type($keys)));
        }
        foreach ($keys as $key => $value) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        $redis = $this->getRedis();
        $key = $this->getCompleteKey($key);
        return boolval($redis->exists($key));
    }

    private function getRedis(){
        return $this->redis;
    }

    /**
     * @param string $key
     * @return string
     * @author Weida
     */
    private function getCompleteKey(string $key):string {
        if(!empty($this->prefix)){
            return $this->prefix.":".$key;
        }
        return $key;
    }
}