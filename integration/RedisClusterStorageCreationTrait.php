<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisClusterIntegration;

use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisCluster;
use RedisCluster as RedisClusterFromExtension;
use RuntimeException;
use Laminas\Cache\Storage\Plugin\Serializer;

use function getenv;
use function posix_getpid;
use function uniqid;

trait RedisClusterStorageCreationTrait
{
    /** @var RedisCluster */
    private $storage;

    private function createRedisClusterStorage(int $serializerOption, bool $serializerPlugin) : RedisCluster
    {
        if ($this->storage) {
            return $this->storage;
        }

        $node = getenv('TESTS_ZEND_CACHE_REDIS_CLUSTER_NODENAME') ?? '';
        if (! $node) {
            throw new RuntimeException('Could not find nodename environment configuration.');
        }

        $options = [
            'nodename'    => $node,
            'lib_options' => [
                RedisClusterFromExtension::OPT_SERIALIZER => $serializerOption,
            ],
            'namespace'   => uniqid((string) posix_getpid(), true),
        ];

        $storage = new RedisCluster($options);
        if ($serializerOption === RedisClusterFromExtension::SERIALIZER_NONE && $serializerPlugin) {
            $storage->addPlugin(new Serializer());
        }

        return $this->storage = $storage;
    }
}
