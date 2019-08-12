<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster;

use RedisCluster as RedisClusterFromExtension;
use Zend\Cache\Storage\Adapter\AbstractAdapter;

interface RedisClusterResourceManagerInterface
{
    public function getVersion() : string;

    /**
     * @inheritDoc
     */
    public function getResource() : RedisClusterFromExtension;

    public function getLibOption(int $option) : int;

    public function hasSerializationSupport(AbstractAdapter $adapter) : bool;
}
