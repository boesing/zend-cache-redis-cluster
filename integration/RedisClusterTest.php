<?php

declare(strict_types=1);

namespace Boesing\Laminas\Cache\Storage\Adapter\RedisClusterIntegration;

use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisCluster;
use Boesing\Laminas\Cache\Storage\Adapter\RedisCluster\RedisClusterOptions;
use PHPUnit\Framework\TestCase;
use RedisCluster as RedisClusterFromExtension;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Plugin\Serializer;

final class RedisClusterTest extends TestCase
{
    use RedisClusterStorageCreationTrait;

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

    /**
     * @test
     */
    public function clearsByNamespace()
    {
        $namespace        = 'foo';
        $anotherNamespace = 'bar';
        $storage          = $this->storage;
        $options          = $storage->getOptions();
        $options->setNamespace($namespace);

        $storage->setItem('bar', 'baz');
        $storage->setItem('qoo', 'ooq');

        $options->setNamespace($anotherNamespace);

        $storage->setItem('bar', 'baz');
        $storage->setItem('qoo', 'ooq');

        $storage->clearByNamespace($namespace);

        $options->setNamespace($namespace);

        $result = $storage->getItems(['bar', 'qoo']);
        $this->assertEmpty($result);

        $options->setNamespace($anotherNamespace);
        $result = $storage->getItems(['bar', 'qoo']);
        $this->assertEquals($result['bar'], 'baz');
        $this->assertEquals($result['qoo'], 'ooq');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->storage = $this->createRedisClusterStorage(RedisClusterFromExtension::SERIALIZER_NONE, true);
        // Clear storage before executing tests.
        $this->storage->flush();
    }

    /**
     * Remove the property cache as we do want to create a new instance for the next test.
     */
    protected function tearDown()
    {
        $this->storage = null;
        parent::tearDown();
    }
}
