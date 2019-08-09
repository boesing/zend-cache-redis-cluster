<?php

declare(strict_types=1);

/**
 * This is needed for cache/integration-tests as they depend on old phpunit versions.
 */

use PHPUnit\Framework\TestCase;

if (! class_exists(phpunit_framework_testcase::class)) {
    class_alias(TestCase::class, phpunit_framework_testcase::class);
}
