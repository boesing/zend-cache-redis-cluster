<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisClusterTest;

use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisClusterOptions;
use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisClusterResourceManager;
use PHPUnit\Framework\TestCase;
use RedisCluster;
use SplObjectStorage;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Plugin\Serializer;

use function uniqid;

final class RedisClusterResourceManagerTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationSupportOptionsProvider
     */
    public function canDetectSerializationSupportFromOptions(RedisClusterOptions $options)
    {
        $manager = new RedisClusterResourceManager($options);
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter
            ->expects($this->never())
            ->method('getPluginRegistry');

        $this->assertTrue($manager->hasSerializationSupport($adapter));
    }

    /**
     * @test
     */
    public function canDetectSerializationSupportFromSerializerPlugin()
    {
        $registry = $this->createMock(SplObjectStorage::class);
        $registry
            ->expects($this->any())
            ->method('current')
            ->willReturn(new Serializer());

        $registry
            ->expects($this->once())
            ->method('valid')
            ->willReturn(true);

        $manager = new RedisClusterResourceManager(new RedisClusterOptions([
            'nodename' => uniqid(),
        ]));
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter
            ->expects($this->once())
            ->method('getPluginRegistry')
            ->willReturn($registry);

        $this->assertTrue($manager->hasSerializationSupport($adapter));
    }

    /**
     * @test
     */
    public function willReturnVersionFromOptions()
    {
        $manager = new RedisClusterResourceManager(new RedisClusterOptions([
            'nodename'      => uniqid(),
            'redis_version' => '1.0.0',
        ]));

        $version = $manager->getVersion();
        $this->assertEquals('1.0.0', $version);
    }

    public function serializationSupportOptionsProvider()
    {
        return [
            'php-serialize'      => [
                new RedisClusterOptions([
                    'nodename'    => uniqid(),
                    'lib_options' => [
                        RedisCluster::OPT_SERIALIZER => RedisCluster::SERIALIZER_PHP,
                    ],
                ]),
            ],
            'igbinary-serialize' => [
                new RedisClusterOptions([
                    'nodename'    => uniqid(),
                    'lib_options' => [
                        RedisCluster::OPT_SERIALIZER => RedisCluster::SERIALIZER_IGBINARY,
                    ],
                ]),
            ],
        ];
    }
}
