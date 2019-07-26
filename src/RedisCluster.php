<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster;

use function array_key_exists;
use Redis;
use RedisCluster as RedisClusterFromExtension;
use RedisClusterException;
use RedisException;
use stdClass;
use Zend\Cache\Exception;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;

final class RedisCluster extends AbstractAdapter implements FlushableInterface, TotalSpaceCapableInterface
{

    /** @var RedisClusterFromExtension|null */
    private $client;

    /**
     * @var string
     */
    private $namespacePrefix = '';

    private $libOptions = [];

    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->getEventManager()->attach('option', function (): void {
            $this->client = null;
            $this->capabilities = null;
            $this->capabilityMarker = null;
            $this->libOptions = [];
        });
    }

    public function setOptions($options)
    {
        if (!$options instanceof RedisClusterOptions) {
            $options = new RedisClusterOptions($options);
        }

        $namespace = $options->getNamespace();
        if ($namespace !== '') {
            $this->namespacePrefix = $namespace . $options->getNamespaceSeparator();
        }

        return parent::setOptions($options);
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        $resource = $this->getRedisResource();
        $success = true;
        foreach ($resource->_masters() as [$host, $port]) {
            $redis = new Redis();
            try {
                $redis->connect($host, $port);
                $success &= $redis->flushDB();
            } catch (RedisException $exception) {
                throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
            }
        }

        return (bool) $success;
    }

    private function getRedisResource(): RedisClusterFromExtension
    {
        if ($this->client instanceof RedisClusterFromExtension) {
            return $this->client;
        }

        try {
            return $this->client = $this->createRedisResource($this->getOptions());
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException('Could not establish connection to redis cluster', 0, $exception);
        }
    }

    private function createRedisResource(RedisClusterOptions $getOptions): RedisClusterFromExtension
    {
        $options = $this->getOptions();
        if ($options->hasNodename()) {
            return new RedisClusterFromExtension(
                $options->nodename(),
                [],
                $options->timeout(),
                $options->readTimeout(),
                $options->persistent()
            );
        }

        return new RedisClusterFromExtension(null, $options->seeds(), $options->timeout(), $options->readTimeout(),
            $options->persistent());
    }

    public function getOptions(): RedisClusterOptions
    {
        /** @var RedisClusterOptions $options */
        $options = parent::getOptions();

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function getTotalSpace()
    {
        $redis = $this->getRedisResource();
        try {
            $info = $redis->info();
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }

        return $info['used_memory'] ?? 0;
    }

    /**
     * @inheritDoc
     */
    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        $redis = $this->getRedisResource();
        try {
            $value = $redis->get($this->key($normalizedKey));
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }

        if ($value === false) {
            $success = false;

            return null;
        }

        $success = true;
        $casToken = $value;

        return $value;
    }

    private function key(string $key): string
    {
        return $this->namespacePrefix . $key;
    }

    /**
     * @inheritDoc
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $redis = $this->getRedisResource();
        $ttl = $this->getOptions()->getTtl();

        try {
            if ($ttl) {
                $success = $redis->setex($this->key($normalizedKey), $ttl, $value);
            } else {
                $success = $redis->set($this->key($normalizedKey), $value);
            }
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    protected function internalRemoveItem(& $normalizedKey)
    {
        $redis = $this->getRedisResource();

        try {
            return (bool) $redis->del($this->key($normalizedKey));
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }
    }

    protected function internalHasItem(& $normalizedKey)
    {
        $redis = $this->getRedisResource();

        try {
            return (bool) $redis->exists($this->key($normalizedKey));
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }
    }

    protected function internalSetItems(array &$normalizedKeyValuePairs)
    {
        $redis = $this->getRedisResource();
        $ttl = $this->getOptions()->getTtl();

        $namespacedKeyValuePairs = [];
        foreach ($normalizedKeyValuePairs as $normalizedKey => & $value) {
            $namespacedKeyValuePairs[$this->key($normalizedKey)] = &$value;
        }

        try {
            $succcessByIndex = $this->multipleSetToRedis($redis, $namespacedKeyValuePairs, $ttl);
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }

        $namespacedKeys = array_keys($namespacedKeyValuePairs);
        $statuses = [];
        foreach ($succcessByIndex as $index => $success) {
            if ($success) {
                continue;
            }

            if (!is_numeric($index)) {
                $statuses[] = $index;
                continue;
            }

            $statuses[] = $namespacedKeys[$index];
        }

        return $statuses;
    }

    private function multipleSetToRedis(
        RedisClusterFromExtension $redis,
        array $namespacedKeyValuePairs,
        float $ttl
    ): array {
        if (!$ttl) {
            $statuses = [];
            foreach ($namespacedKeyValuePairs as $key => $value) {
                if ($redis->set($key, $value)) {
                    continue;
                }
                $statuses[$key] = true;
            }

            return $statuses;
        }

        $transaction = $redis->multi(Redis::MULTI);
        foreach ($namespacedKeyValuePairs as $key => $value) {
            $transaction->setex($key, $ttl, $value);
        }

        return $transaction->exec();
    }

    protected function internalGetCapabilities()
    {
        if ($this->capabilities === null) {
            $this->capabilityMarker = new stdClass();

            $serializer   = $this->getLibOption($this->getRedisResource(), Redis::OPT_SERIALIZER);
            $redisVersion = $this->getMajorVersion($this->getRedisResource());
            $minTtl       = version_compare($redisVersion, '2', '<') ? 0 : 1;
            $supportedMetadata = $redisVersion >= 2 ? ['ttl'] : [];

            $this->capabilities = new Capabilities(
                $this,
                $this->capabilityMarker,
                [
                    'supportedDatatypes' => $serializer ? [
                        'NULL'     => true,
                        'boolean'  => true,
                        'integer'  => true,
                        'double'   => true,
                        'string'   => true,
                        'array'    => 'array',
                        'object'   => 'object',
                        'resource' => false,
                    ] : [
                        'NULL'     => 'string',
                        'boolean'  => 'string',
                        'integer'  => 'string',
                        'double'   => 'string',
                        'string'   => true,
                        'array'    => false,
                        'object'   => false,
                        'resource' => false,
                    ],
                    'supportedMetadata'  => $supportedMetadata,
                    'minTtl'             => $minTtl,
                    'maxTtl'             => 0,
                    'staticTtl'          => true,
                    'ttlPrecision'       => 1,
                    'useRequestTime'     => false,
                    'maxKeyLength'       => 255,
                    'namespaceIsPrefix'  => true,
                ]
            );
        }

        return $this->capabilities;
    }

    private function getLibOption(RedisClusterFromExtension $redis, int $option)
    {
        if (array_key_exists($option, $this->libOptions)) {
            return $this->libOptions[$option];
        }

        return $this->libOptions[$option] = $redis->getOption($option);
    }
}
