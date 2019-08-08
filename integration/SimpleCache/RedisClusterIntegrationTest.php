<?php
declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration\SimpleCache;

use Boesing\ZendCacheRedisClusterIntegration\RedisClusterStorageCreationTrait;
use Cache\IntegrationTests\SimpleCacheTest;
use Psr\SimpleCache\CacheInterface;
use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;

/**
 * @author Maximilian Bösing <max.boesing@check24.de>
 */
final class RedisClusterIntegrationTest extends SimpleCacheTest
{

    use RedisClusterStorageCreationTrait;

    /**
     * @return CacheInterface that is used in the tests
     */
    public function createSimpleCache()
    {
        $storage = $this->createRedisClusterStorage();

        return new SimpleCacheDecorator($storage);
    }
}
