<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterIntegration;

use Boesing\ZendCacheRedisCluster\RedisCluster;
use Boesing\ZendCacheRedisCluster\RedisClusterOptions;
use PHPUnit\Framework\TestCase;
use RedisCluster as RedisClusterFromExtension;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\Storage\Plugin\Serializer;

final class RedisClusterTest extends TestCase
{
    use RedisClusterStorageCreationTrait;

    /** @var RedisCluster */
    private $storage;

    /**
     * @test
     */
    public function willProperlyFlush()
    {
        $this->storage->setItem('foo', 'bar');
        $flushed = $this->storage->flush();
        $this->assertTrue($flushed);
        $this->assertFalse($this->storage->hasItem('foo'));
    }

    /**
     * @test
     */
    public function canCreateResourceFromSeeds()
    {
        $options = new RedisClusterOptions([
            'seeds' => ['localhost:7000'],
        ]);

        $storage = new RedisCluster($options);
        $this->assertTrue($storage->flush());
    }

    /**
     * @test
     */
    public function willHandleIntegratedSerializerInformation()
    {
        $storage = $this->storage;
        $this->removeSerializer($storage);

        $options = $storage->getOptions();
        $options->setLibOptions([
            RedisClusterFromExtension::OPT_SERIALIZER => RedisClusterFromExtension::SERIALIZER_PHP,
        ]);

        $capabilities = $storage->getCapabilities();
        $dataTypes    = $capabilities->getSupportedDatatypes();
        $this->assertEquals([
            'NULL'     => true,
            'boolean'  => true,
            'integer'  => true,
            'double'   => true,
            'string'   => true,
            'array'    => 'array',
            'object'   => 'object',
            'resource' => false,
        ], $dataTypes);
    }

    private function removeSerializer(AbstractAdapter $storage) : void
    {
        foreach ($storage->getPluginRegistry() as $plugin) {
            if (! $plugin instanceof Serializer) {
                continue;
            }

            $storage->removePlugin($plugin);
        }
    }

    /**
     * @test
     */
    public function willHandleNonSupportedSerializerInformation()
    {
        $storage = $this->storage;
        $this->removeSerializer($storage);
        $options = $storage->getOptions();
        $options->setLibOptions([
            RedisClusterFromExtension::OPT_SERIALIZER => RedisClusterFromExtension::SERIALIZER_NONE,
        ]);

        $capabilities = $storage->getCapabilities();
        $dataTypes    = $capabilities->getSupportedDatatypes();
        $this->assertEquals([
            'NULL'     => 'string',
            'boolean'  => 'string',
            'integer'  => 'string',
            'double'   => 'string',
            'string'   => true,
            'array'    => false,
            'object'   => false,
            'resource' => false,
        ], $dataTypes);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->storage = $this->createRedisClusterStorage(RedisClusterFromExtension::SERIALIZER_NONE, true);
    }
}
