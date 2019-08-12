<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster;

use RedisCluster as RedisClusterFromExtension;

interface RedisClusterResourceManagerInterface
{
    public function getVersion() : string;

    /**
     * @inheritDoc
     */
    public function getResource() : RedisClusterFromExtension;

    public function getLibOption(int $option) : int;
}
