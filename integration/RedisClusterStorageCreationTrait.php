<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration;

use Boesing\ZendCacheRedisCluster\RedisCluster;
use RedisCluster as RedisClusterFromExtension;
use RuntimeException;
use Zend\Cache\Storage\Plugin\Serializer;

use function getenv;

trait RedisClusterStorageCreationTrait
{
    private function createRedisClusterStorage(int $serializerOption, bool $serializerPlugin) : RedisCluster
    {
        $node = getenv('TESTS_ZEND_CACHE_REDIS_CLUSTER_NODENAME') ?? '';
        if (! $node) {
            throw new RuntimeException('Could not find nodename environment configuration.');
        }

        $options['nodename']    = $node;
        $options['lib_options'] = [
            RedisClusterFromExtension::OPT_SERIALIZER => $serializerOption,
        ];

        $storage = new RedisCluster($options);
        if ($serializerOption === RedisClusterFromExtension::SERIALIZER_NONE && $serializerPlugin) {
            $storage->addPlugin(new Serializer());
        }

        return $storage;
    }
}
