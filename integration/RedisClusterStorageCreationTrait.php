<?php
declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration;

use Boesing\ZendCacheRedisCluster\RedisCluster;
use RuntimeException;
use Zend\Cache\Storage\Plugin\Serializer;
use function getenv;

/**
 * @author Maximilian Bösing <max.boesing@check24.de>
 */
trait RedisClusterStorageCreationTrait
{

    private function createRedisClusterStorage(): RedisCluster
    {
        $node = getenv('TESTS_ZEND_CACHE_REDIS_CLUSTER_NODENAME') ?? '';
        if (!$node) {
            throw new RuntimeException('Could not find nodename environment configuration.');
        }

        $options['nodename'] = $node;

        $storage = new RedisCluster($options);
        $storage->addPlugin(new Serializer());

        return $storage;
    }
}
