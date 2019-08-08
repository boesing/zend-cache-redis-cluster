<?php
declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration\CacheItemPool;

use Boesing\ZendCacheRedisClusterIntegration\RedisClusterStorageCreationTrait;
use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;
use Zend\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;

final class RedisClusterIntegrationTest extends CachePoolTest
{

    use RedisClusterStorageCreationTrait;


    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    public function createCachePool()
    {
        $storage = $this->createRedisClusterStorage();
        $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = sprintf(
            '%s storage doesn\'t support driver deferred',
            get_class($storage)
        );

        return new CacheItemPoolDecorator($storage);
    }
}
