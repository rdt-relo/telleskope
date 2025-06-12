<?php

trait CacheableTrait
{
    public function getFromRedisCache(string $key)
    {
        global $_COMPANY;
        $key = $this->getCompanyCacheKey($key);
        return $_COMPANY->getFromRedisCache($key);
    }

    public function putInRedisCache(string $key, ...$args): bool
    {
        global $_COMPANY;
        $key = $this->getCompanyCacheKey($key);
        return $_COMPANY->putInRedisCache($key, ...$args);
    }

    public function expireRedisCache(string $key): bool
    {
        global $_COMPANY;
        $key = $this->getCompanyCacheKey($key);
        return $_COMPANY->expireRedisCache($key);
    }

    private function getCompanyCacheKey(string $key): string
    {
        return $this->getCurrentTopicType() . ':' . $this->id() . ':' . $key;
    }
}
