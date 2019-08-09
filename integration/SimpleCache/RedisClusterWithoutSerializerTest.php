<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration\SimpleCache;

use Boesing\ZendCacheRedisClusterIntegration\RedisClusterStorageCreationTrait;
use Cache\IntegrationTests\SimpleCacheTest;
use Psr\SimpleCache\CacheInterface;
use RedisCluster;
use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;

final class RedisClusterWithoutSerializerTest extends SimpleCacheTest
{
    use RedisClusterStorageCreationTrait;

    /**
     * @return CacheInterface that is used in the tests
     */
    public function createSimpleCache()
    {
        $storage = $this->createRedisClusterStorage(RedisCluster::SERIALIZER_NONE, true);

        return new SimpleCacheDecorator($storage);
    }
}
