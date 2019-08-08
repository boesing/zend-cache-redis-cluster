<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster;

use Boesing\ZendCacheRedisCluster\Exception\InvalidConfiguration;
use Webmozart\Assert\Assert;
use Zend\Cache\Storage\Adapter\AdapterOptions;
use function array_keys;

final class RedisClusterOptions extends AdapterOptions
{
    /** @var string */
    protected $namespaceSeparator = ':';

    /** @var string */
    protected $nodename = '';

    /** @var float */
    protected $timeout = 0.0;

    /** @var float */
    protected $readTimeout = 0.0;

    /** @var bool */
    protected $persistent = false;

    /** @var array<int,string> */
    protected $seeds = [];

    /** @var string */
    protected $version = '';

    /** @var array<int,mixed> */
    protected $libOptions = [];

    /**
     * @inheritDoc
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        if (! $this->hasNodename() && empty($this->seeds)) {
            throw InvalidConfiguration::fromMissingRequiredValues();
        }

        if ($this->hasNodename() && ! empty($this->seeds)) {
            throw InvalidConfiguration::nodenameAndSeedsProvided();
        }
    }

    public function setTimeout(float $timeout) : void
    {
        $this->timeout = $timeout;
        $this->triggerOptionEvent('timeout', $timeout);
    }

    public function setReadTimeout(float $readTimeout) : void
    {
        $this->readTimeout = $readTimeout;
        $this->triggerOptionEvent('read_timeout', $readTimeout);
    }

    public function setPersistent(bool $persistent) : void
    {
        $this->persistent = $persistent;
    }

    public function getNamespaceSeparator() : string
    {
        return $this->namespaceSeparator;
    }

    public function setNamespaceSeparator(string $namespaceSeparator) : void
    {
        if ($this->namespaceSeparator === $namespaceSeparator) {
            return;
        }

        $this->triggerOptionEvent('namespace_separator', $namespaceSeparator);
        $this->namespaceSeparator = $namespaceSeparator;
    }

    public function hasNodename() : bool
    {
        return $this->nodename !== '';
    }

    public function nodename() : string
    {
        return $this->nodename;
    }

    public function setNodename(string $nodename) : void
    {
        $this->nodename = $nodename;
        $this->triggerOptionEvent('nodename', $nodename);
    }

    public function timeout() : float
    {
        return $this->timeout;
    }

    public function readTimeout() : float
    {
        return $this->readTimeout;
    }

    public function persistent() : bool
    {
        return $this->persistent;
    }

    /**
     * @return string[]
     */
    public function seeds() : array
    {
        return $this->seeds;
    }

    /**
     * @param string[] $seeds
     */
    public function setSeeds(array $seeds) : void
    {
        Assert::notEmpty($seeds);
        Assert::allString($seeds);
        $this->seeds = $seeds;

        $this->triggerOptionEvent('seeds', $seeds);
    }

    public function setRedisVersion(string $version) : void
    {
        Assert::stringNotEmpty($version);
        Assert::regex($version, '#^\d+\.\d+#');
        $this->version = $version;
    }

    public function redisVersion() : string
    {
        return $this->version;
    }

    /**
     * @param array<int,mixed> $options
     */
    public function setLibOptions(array $options) : void
    {
        Assert::allInteger(array_keys($options));
        $this->libOptions = $options;
    }

    /**
     * @return array<int,mixed>
     */
    public function libOptions() : array
    {
        return $this->libOptions;
    }
}
