<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration\SimpleCache;

use Boesing\ZendCacheRedisClusterIntegration\RedisClusterStorageCreationTrait;
use Cache\IntegrationTests\SimpleCacheTest;
use Psr\SimpleCache\CacheInterface;
use RedisCluster;
use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;

final class RedisClusterWithPhpIgbinaryTest extends SimpleCacheTest
{
    use RedisClusterStorageCreationTrait;

    /**
     * @return CacheInterface that is used in the tests
     */
    public function createSimpleCache()
    {
        $storage = $this->createRedisClusterStorage(RedisCluster::SERIALIZER_IGBINARY, false);

        return new SimpleCacheDecorator($storage);
    }

    /**
     * Remove the property cache as we do want to create a new instance for the next test.
     */
    protected function tearDown()
    {
        $this->storage = null;
        parent::tearDown();
    }
}
