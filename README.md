# laminas-cache-storage-adapter-redis-cluster


`laminas/laminas-cache` adapter to provide `RedisCluster` support to projects using `laminas-cache`.


## Installation

```bash
composer require boesing/laminas-cache-storage-adapter-redis-cluster
```

## Configuration

```php
use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisCluster;

return [
    'caches' => [
        /**
         * NOTE: the cluster nodename must exist in your php.ini!
         * If you configure timeout in your php.ini per nodename aswell, there is no need to
         * configure more than the nodename.
         */
        'redis-cluster-cache-with-nodename' => [
            'adapter' => [
                'name' => RedisCluster::class,
                'options' => [
                    'namespace' => '',
                    'namespace_separator' => ':',
                    'nodename' => 'clusternode1',
                    'persistent' => false,
                    // You can provide the redis version by configuration to avoid an info call on each connect
                    'redis_version' => '',
                ],
            ],
        ],
        'redis-cluster-cache-with-seeds' => [
            'adapter' => [
                'name' => RedisCluster::class,
                'options' => [
                    'namespace' => '',
                    'namespace_separator' => ':',
                    'seeds' => ["hostname:port", "hostname2:port2", /* ... */],
                    'timeout' => 1,
                    'readTimeout' => 2,
                    'persistent' => false,
                    // You can provide the redis version by configuration to avoid an info call on each connect
                    'redis_version' => '',
                ],
            ],
        ],
    ],
];
```
