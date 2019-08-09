<?php

declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterTest;

use Boesing\ZendCacheRedisCluster\Exception\InvalidConfigurationException;
use Boesing\ZendCacheRedisCluster\RedisClusterOptionsFromIni;
use PHPUnit\Framework\TestCase;

use function ini_get;
use function ini_set;

final class RedisClusterOptionsFromIniTest extends TestCase
{
    /** @var string */
    private $seedsConfigurationFromIni;

    /**
     * @test
     */
    public function willThrowExceptionOnMissingSeedsConfiguration()
    {
        $this->expectException(InvalidConfigurationException::class);
        new RedisClusterOptionsFromIni();
    }

    /**
     * @test
     * @dataProvider seedsByNodenameProvider
     */
    public function willDetectSeedsByNodename(string $nodename, string $config, array $expected)
    {
        ini_set('redis.clusters.seeds', $config);
        $options = new RedisClusterOptionsFromIni();
        $seeds   = $options->seeds($nodename);
        $this->assertEquals($expected, $seeds);
    }

    /**
     * @test
     */
    public function willThrowExceptionOnMissingNodenameInSeeds()
    {
        ini_set('redis.clusters.seeds', 'foo[]=bar:123');
        $options = new RedisClusterOptionsFromIni();
        $this->expectException(InvalidConfigurationException::class);
        $options->seeds('bar');
    }

    public function seedsByNodenameProvider()
    {
        return [
            'simple'         => [
                'foo',
                'foo[]=localhost:1234',
                ['localhost:1234'],
            ],
            'multiple seeds' => [
                'bar',
                'bar[]=localhost:4321&bar[]=localhost:1234',
                ['localhost:4321', 'localhost:1234'],
            ],
            'multiple nodes' => [
                'baz',
                'foo[]=localhost:7000&foo[]=localhost=7001&baz[]=localhost:7002&baz[]=localhost:7003',
                ['localhost:7002', 'localhost:7003'],
            ],
        ];
    }

    protected function setUp()
    {
        parent::setUp();
        $this->seedsConfigurationFromIni = ini_get('redis.clusters.seeds');
        ini_set('redis.clusters.seeds', '');
    }

    protected function tearDown()
    {
        parent::tearDown();
        ini_set('redis.clusters.seeds', $this->seedsConfigurationFromIni);
    }
}
