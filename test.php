<?php

require __DIR__ . '/vendor/autoload.php';

use Boesing\ZendCacheRedisCluster\RedisClusterOptions;
use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;

$storage = new \Boesing\ZendCacheRedisCluster\RedisCluster(new RedisClusterOptions([
    'nodename' => 'cache',
    'lib_options' => [
        RedisCluster::OPT_SERIALIZER => RedisCluster::SERIALIZER_PHP,
    ],
]));

$cache = new SimpleCacheDecorator($storage);

$cache->set('foo', 'bar');

while (true) {
    var_dump($cache->has('foo'));
    sleep(1);
}
