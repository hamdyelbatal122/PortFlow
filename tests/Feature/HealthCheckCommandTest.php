<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Feature;

use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Testing\PendingCommand;

final class HealthCheckCommandTest extends TestCase
{
    public function test_health_check_command_passes_when_system_healthy(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:health');
        $command->expectsOutputToContain('PortFlow Health Check')
            ->expectsOutputToContain('Cache Connectivity')
            ->expectsOutputToContain('Registered Drivers')
            ->expectsOutputToContain('Event Mappings')
            ->expectsOutputToContain('All PortFlow subsystems are healthy')
            ->assertExitCode(0);
    }

    public function test_health_check_command_returns_valid_json(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:health', ['--json' => true]);
        $command->assertExitCode(0);
    }
}
