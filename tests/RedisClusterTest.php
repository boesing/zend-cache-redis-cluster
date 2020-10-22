<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisClusterTest;

use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisCluster;
use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisClusterResourceManagerInterface;
use PHPUnit\Framework\TestCase;

final class RedisClusterTest extends TestCase
{
    /**
     * @test
     */
    public function canDetectCapabilitiesWithSerializationSupport()
    {
        $resourceManager = $this->createMock(RedisClusterResourceManagerInterface::class);

        $adapter = new RedisCluster([
            'nodename' => 'bar',
        ]);
        $adapter->getOptions()->setResourceManager($resourceManager);

        $resourceManager
            ->expects($this->once())
            ->method('hasSerializationSupport')
            ->with($adapter)
            ->willReturn(true);

        $resourceManager
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn('5.0.0');

        $capabilities = $adapter->getCapabilities();
        $datatypes    = $capabilities->getSupportedDatatypes();
        $this->assertEquals([
            'NULL'     => true,
            'boolean'  => true,
            'integer'  => true,
            'double'   => true,
            'string'   => true,
            'array'    => 'array',
            'object'   => 'object',
            'resource' => false,
        ], $datatypes);
    }

    /**
     * @test
     */
    public function canDetectCapabilitiesWithoutSerializationSupport()
    {
        $resourceManager = $this->createMock(RedisClusterResourceManagerInterface::class);

        $adapter = new RedisCluster([
            'nodename' => 'bar',
        ]);
        $adapter->getOptions()->setResourceManager($resourceManager);

        $resourceManager
            ->expects($this->once())
            ->method('hasSerializationSupport')
            ->with($adapter)
            ->willReturn(false);

        $resourceManager
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn('5.0.0');

        $capabilities = $adapter->getCapabilities();
        $datatypes    = $capabilities->getSupportedDatatypes();
        $this->assertEquals([
            'NULL'     => 'string',
            'boolean'  => 'string',
            'integer'  => 'string',
            'double'   => 'string',
            'string'   => true,
            'array'    => false,
            'object'   => false,
            'resource' => false,
        ], $datatypes);
    }
}
