<?php
declare(strict_types=1);

namespace Boesing\ZendCacheRedisClusterTest;

use Boesing\ZendCacheRedisCluster\Exception\InvalidConfiguration;
use PHPUnit\Framework\TestCase;
use Zend\Cache\Exception\ExceptionInterface;

/**
 * @author Maximilian Bösing <max.boesing@check24.de>
 */
final class InvalidConfigurationExceptionTest extends TestCase
{

    /**
     * @test
     */
    public function instanceOfZendCacheException()
    {
        $exception = new InvalidConfiguration();
        $this->assertInstanceOf(ExceptionInterface::class, $exception);
    }
}
