<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster;

use Webmozart\Assert\Assert;
use Zend\Cache\Storage\Adapter\AdapterOptions;

final class RedisClusterOptions extends AdapterOptions
{
    private $namespaceSeparator = ':';

    /** @var string */
    private $nodename = '';

    /** @var float|null */
    private $timeout;

    /** @var float|null */
    private $readTimeout;

    /** @var bool */
    private $persistent;

    /**
     * @var array<int,string>
     */
    private $seeds;

    public function setTimeout(?float $timeout) : void
    {
        $this->timeout = $timeout;
    }

    public function setReadTimeout(?float $readTimeout) : void
    {
        $this->readTimeout = $readTimeout;
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
    }

    public function timeout() : ?float
    {
        return $this->timeout;
    }

    public function readTimeout() : ?float
    {
        return $this->readTimeout;
    }

    public function persistent() : bool
    {
        return $this->persistent;
    }

    public function seeds(): array
    {
        return $this->seeds;
    }

    public function setSeeds(array $seeds): void
    {
        Assert::allString($seeds);
        $this->seeds = $seeds;
    }
}
