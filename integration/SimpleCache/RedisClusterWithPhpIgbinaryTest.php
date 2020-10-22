<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisClusterIntegration\SimpleCache;

use Boesing\Laminas\Cache\Storage\Adapter\RedisClusterIntegration\RedisClusterStorageCreationTrait;
use Cache\IntegrationTests\SimpleCacheTest;
use Psr\SimpleCache\CacheInterface;
use RedisCluster;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;

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
