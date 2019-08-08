<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster;

use Redis;
use RedisCluster as RedisClusterFromExtension;
use RedisClusterException;
use RedisException;
use stdClass;
use Zend\Cache\Exception;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\Storage\Capabilities;
use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\Plugin\Serializer;
use Zend\Cache\Storage\TotalSpaceCapableInterface;
use function array_key_exists;
use function is_array;
use function version_compare;

final class RedisCluster extends AbstractAdapter implements FlushableInterface, TotalSpaceCapableInterface
{
    /** @var RedisClusterFromExtension|null */
    private $resource;

    /** @var string */
    private $namespacePrefix = '';

    /** @var array<int,mixed> */
    private $libOptions = [];

    /**
     * @inheritDoc
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->getEventManager()->attach('option', function () : void {
            $this->resource         = null;
            $this->capabilities     = null;
            $this->capabilityMarker = null;
            $this->libOptions       = [];
        });
    }

    /**
     * @inheritDoc
     */
    public function setOptions($options)
    {
        if (! $options instanceof RedisClusterOptions) {
            $options = new RedisClusterOptions($options);
        }

        $namespace = $options->getNamespace();
        if ($namespace !== '') {
            $this->namespacePrefix = $namespace . $options->getNamespaceSeparator();
        }

        $options->setAdapter($this);

        return parent::setOptions($options);
    }

    /**
     * @inheritDoc
     */
    public function flush() : bool
    {
        $resource = $this->getRedisResource();
        $success  = true;
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

    private function getRedisResource() : RedisClusterFromExtension
    {
        if ($this->resource instanceof RedisClusterFromExtension) {
            return $this->resource;
        }
        $options = $this->getOptions();

        try {
            $resource         = $this->createRedisResource($options);
            $this->libOptions = $options->libOptions();
            foreach ($this->libOptions as $option => $value) {
                $resource->setOption($option, $value);
            }

            return $this->resource = $resource;
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException('Could not establish connection to redis cluster', 0, $exception);
        }
    }

    public function getOptions() : RedisClusterOptions
    {
        /** @var RedisClusterOptions $options */
        $options = parent::getOptions();

        return $options;
    }

    private function createRedisResource(RedisClusterOptions $options) : RedisClusterFromExtension
    {
        if ($options->hasNodename()) {
            return $this->createRedisResourceFromNodename(
                $options->nodename(),
                $options->timeout(),
                $options->readTimeout(),
                $options->persistent()
            );
        }

        return new RedisClusterFromExtension(
            null,
            $options->seeds(),
            $options->timeout(),
            $options->readTimeout(),
            $options->persistent()
        );
    }

    private function createRedisResourceFromNodename(
        string $nodename,
        float $fallbackTimeout,
        float $fallbackReadTimeout,
        bool $persistent
    ) : RedisClusterFromExtension {
        $options     = new RedisClusterOptionsFromIni();
        $seeds       = $options->seeds($nodename);
        $timeout     = $options->timeout($nodename, $fallbackTimeout);
        $readTimeout = $options->readTimeout($nodename, $fallbackReadTimeout);

        return new RedisClusterFromExtension(null, $seeds, $timeout, $readTimeout, $persistent);
    }

    /**
     * @inheritDoc
     */
    public function getTotalSpace() : int
    {
        $redis = $this->getRedisResource();
        try {
            $info = $redis->info();
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }

        return (int) ($info['used_memory'] ?? 0);
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

        $success  = true;
        $casToken = $value;

        return $value;
    }

    private function key(string $key) : string
    {
        return $this->namespacePrefix . $key;
    }

    /**
     * @inheritDoc
     */
    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $redis = $this->getRedisResource();
        $ttl   = $this->getOptions()->getTtl();

        try {
            if ($ttl) {
                return $redis->setex($this->key($normalizedKey), $ttl, $value);
            }

            return $redis->set($this->key($normalizedKey), $value);
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }
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

    /**
     * @inheritDoc
     */
    protected function internalHasItem(& $normalizedKey)
    {
        $redis = $this->getRedisResource();

        try {
            return (bool) $redis->exists($this->key($normalizedKey));
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    protected function internalSetItems(array &$normalizedKeyValuePairs)
    {
        $redis = $this->getRedisResource();
        $ttl   = (int) $this->getOptions()->getTtl();

        $namespacedKeyValuePairs = [];
        foreach ($normalizedKeyValuePairs as $normalizedKey => $value) {
            $namespacedKeyValuePairs[$this->key((string) $normalizedKey)] = $value;
        }

        try {
            $successByKey = [];
            foreach ($namespacedKeyValuePairs as $key => $value) {
                $successByKey[$key] = $ttl ? $redis->setex($key, $ttl, $value) : $redis->set($key, $value);
            }
        } catch (RedisClusterException $exception) {
            throw new Exception\RuntimeException($redis->getLastError(), $exception->getCode(), $exception);
        }

        $statuses = [];
        foreach ($successByKey as $key => $success) {
            if ($success) {
                continue;
            }

            $statuses[] = $key;
        }

        return $statuses;
    }

    /**
     * @inheritDoc
     */
    protected function internalGetCapabilities() : Capabilities
    {
        if ($this->capabilities !== null) {
            return $this->capabilities;
        }

        $redis                  = $this->getRedisResource();
        $this->capabilityMarker = new stdClass();

        $serializer = (bool) $this->getLibOption($redis, RedisClusterFromExtension::OPT_SERIALIZER);
        if (! $serializer) {
            $serializer = $this->hasPlugin(new Serializer());
        }

        $redisVersion = $this->detectRedisVersion($redis, $this->getOptions());

        $redisVersionLessThanV2 = version_compare($redisVersion, '2.0', '<');
        $minTtl                 = $redisVersionLessThanV2 ? 0 : 1;
        $supportedMetadata      = $redisVersionLessThanV2 ? ['ttl'] : [];

        $this->capabilities = new Capabilities(
            $this,
            $this->capabilityMarker,
            [
                'supportedDatatypes' => $this->supportedDatatypes($serializer),
                'supportedMetadata' => $supportedMetadata,
                'minTtl' => $minTtl,
                'maxTtl' => 0,
                'staticTtl' => true,
                'ttlPrecision' => 1,
                'useRequestTime' => false,
                'maxKeyLength' => 255,
                'namespaceIsPrefix' => true,
            ]
        );

        return $this->capabilities;
    }

    /**
     * @return mixed
     */
    private function getLibOption(RedisClusterFromExtension $redis, int $option)
    {
        if (array_key_exists($option, $this->libOptions)) {
            return $this->libOptions[$option];
        }

        return $this->libOptions[$option] = $redis->getOption($option);
    }

    private function detectRedisVersion(RedisClusterFromExtension $redis, RedisClusterOptions $options) : string
    {
        $version = $options->redisVersion();
        if ($version) {
            return $version;
        }

        $version = $redis->info('redis_version');

        /** @see https://github.com/phpredis/phpredis/issues/1616 */
        if (is_array($version)) {
            return $version['redis_version'];
        }

        return $version;
    }

    /**
     * @return array<string,mixed>
     */
    private function supportedDatatypes(bool $serializer) : array
    {
        if ($serializer) {
            return [
                'NULL' => true,
                'boolean' => true,
                'integer' => true,
                'double' => true,
                'string' => true,
                'array' => 'array',
                'object' => 'object',
                'resource' => false,
            ];
        }

        return [
            'NULL' => 'string',
            'boolean' => 'string',
            'integer' => 'string',
            'double' => 'string',
            'string' => true,
            'array' => false,
            'object' => false,
            'resource' => false,
        ];
    }
}
