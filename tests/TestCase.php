<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests;

use Hamzi\PortFlow\PortFlowServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PortFlowServiceProvider::class];
    }
}
