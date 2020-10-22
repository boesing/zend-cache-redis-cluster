<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration\CacheItemPool;

use Boesing\ZendCacheRedisClusterIntegration\RedisClusterStorageCreationTrait;
use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;
use RedisCluster;
use Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;

use function get_class;
use function sprintf;

final class RedisClusterWithPhpIgbinaryTest extends CachePoolTest
{
    use RedisClusterStorageCreationTrait;

    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    public function createCachePool()
    {
        $storage = $this->createRedisClusterStorage(RedisCluster::SERIALIZER_IGBINARY, false);
        $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = sprintf(
            '%s storage doesn\'t support driver deferred',
            get_class($storage)
        );

        return new CacheItemPoolDecorator($storage);
    }
}
