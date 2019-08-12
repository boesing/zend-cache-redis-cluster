<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster\Exception;

use RedisCluster;
use RedisClusterException;
use Throwable;
use Zend\Cache\Exception\RuntimeException as ZendCacheRuntimeException;

final class RuntimeException extends ZendCacheRuntimeException
{
    public static function fromClusterException(RedisClusterException $exception, RedisCluster $redis) : self
    {
        $message = $redis->getLastError() ?? $exception->getMessage();

        return new self($message, $exception->getCode(), $exception);
    }

    public static function connectionFailed(Throwable $exception) : self
    {
        return new self('Could not establish connection to redis cluster', $exception->getCode(), $exception);
    }
}
