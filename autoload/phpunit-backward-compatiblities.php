<?php

/**
 * This is needed for cache/integration-tests as they depend on old phpunit versions.
 */

use PHPUnit\Framework\TestCase;

if (!class_exists(PHPUnit_Framework_TestCase::class)) {
    class_alias(TestCase::class, PHPUnit_Framework_TestCase::class);
}
