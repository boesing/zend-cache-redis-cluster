# zend-cache-redis-cluster

[![Build Status](https://travis-ci.org/boesing/zend-cache-redis-cluster.svg?branch=master)](https://travis-ci.org/boesing/zend-cache-redis-cluster)
[![Coverage Status](https://coveralls.io/repos/github/boesing/zend-cache-redis-cluster/badge.svg?branch=master)](https://coveralls.io/github/boesing/zend-cache-redis-cluster?branch=master)


`zendframework/zend-cache` adapter to provide `RedisCluster` support to projects using `zend-cache`.


## Installation

```bash
composer require boesing/zend-cache-redis-cluster
```

## Configuration

```php
use Boesing\ZendCacheRedisCluster\RedisCluster;

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
