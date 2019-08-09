<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterTest;

use Boesing\ZendCacheRedisCluster\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use Zend\Cache\Exception\ExceptionInterface;

final class InvalidConfigurationExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function instanceOfZendCacheException()
    {
        $exception = new InvalidConfigurationException();
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
    }
}
