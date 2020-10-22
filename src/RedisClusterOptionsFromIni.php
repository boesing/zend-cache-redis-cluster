<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisCluster;

use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\Exception\InvalidConfigurationException;
use Webmozart\Assert\Assert;

use function ini_get;
use function parse_str;

final class RedisClusterOptionsFromIni
{
    /** @var array<string,array<int,string>> */
    private $seedsByNodename;

    /** @var array<string,float> */
    private $timeoutByNodename;

    /** @var array<string,float> */
    private $readTimeoutByNodename;

    public function __construct()
    {
        $seedsConfiguration = ini_get('redis.clusters.seeds') ?: '';
        if (! $seedsConfiguration) {
            throw InvalidConfigurationException::fromMissingSeedsConfiguration();
        }

        $seedsByNodename = [];
        parse_str($seedsConfiguration, $seedsByNodename);
        Assert::allIsArray($seedsByNodename);
        foreach ($seedsByNodename as $seeds) {
            Assert::allString($seeds);
        }

        $this->seedsByNodename = $seedsByNodename;

        $timeoutConfiguration = ini_get('redis.clusters.timeout') ?: '';
        $timeoutByNodename    = [];
        parse_str($timeoutConfiguration, $timeoutByNodename);
        Assert::allString($timeoutByNodename);
        foreach ($timeoutByNodename as $nodename => $timeout) {
            $timeoutByNodename[$nodename] = (float) $timeout;
        }
        $this->timeoutByNodename = $timeoutByNodename;

        $readTimeoutConfiguration = ini_get('redis.clusters.read_timeout') ?: '';
        $readTimeoutByNodename    = [];
        parse_str($readTimeoutConfiguration, $readTimeoutByNodename);
        Assert::allString($readTimeoutByNodename);
        foreach ($readTimeoutByNodename as $nodename => $readTimeout) {
            $readTimeoutByNodename[$nodename] = (float) $readTimeout;
        }

        $this->readTimeoutByNodename = $readTimeoutByNodename;
    }

    /**
     * @return string[]
     */
    public function seeds(string $nodename) : array
    {
        $seeds = $this->seedsByNodename[$nodename] ?? [];
        if (! $seeds) {
            throw InvalidConfigurationException::fromInvalidSeedsConfiguration($nodename);
        }

        return $seeds;
    }

    public function timeout(string $nodename, float $fallback) : float
    {
        return $this->timeoutByNodename[$nodename] ?? $fallback;
    }

    public function readTimeout(string $nodename, float $fallback) : float
    {
        return $this->readTimeoutByNodename[$nodename] ?? $fallback;
    }
}
