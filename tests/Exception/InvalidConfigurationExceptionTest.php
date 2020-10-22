<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisClusterTest;

use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use Laminas\Cache\Exception\ExceptionInterface;

final class InvalidConfigurationExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function instanceOfLaminasCacheException()
    {
        $exception = new InvalidConfigurationException();
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
    }
}
