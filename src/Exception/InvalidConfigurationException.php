<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisCluster\Exception;

use Zend\Cache\Exception\InvalidArgumentException;

use function sprintf;

final class InvalidConfigurationException extends InvalidArgumentException
{
    public static function fromMissingSeedsConfiguration() : self
    {
        return new self('Could not find `redis.clusters.seeds` entry in the php.ini file(s).');
    }

    public static function fromInvalidSeedsConfiguration(string $nodename) : self
    {
        return new self(sprintf(
            'Missing `%s` within the configured `redis.cluster.seeds` entry in the php.ini file(s).',
            $nodename
        ));
    }

    public static function fromMissingRequiredValues() : self
    {
        return new self('Missing either `nodename` or `seeds`.');
    }

    public static function nodenameAndSeedsProvided() : self
    {
        return new self('Please provide either `nodename` or `seeds` configuration, not both.');
    }
}
