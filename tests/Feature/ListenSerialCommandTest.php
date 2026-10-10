<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Feature;

use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Testing\PendingCommand;

final class ListenSerialCommandTest extends TestCase
{
    public function test_it_rejects_invalid_device_path(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:listen', [
            'device' => '/invalid/path/traversal/../tty',
        ]);

        $command->expectsOutputToContain('Invalid serial device path')
            ->assertExitCode(1);
    }

    public function test_it_rejects_disallowed_device_by_policy(): void
    {
        config(['portflow.backend.allowed_devices' => ['/dev/ttyUSB0']]);

        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:listen', [
            'device' => '/dev/ttyS1',
        ]);

        $command->expectsOutputToContain('Device is not allowed')
            ->assertExitCode(1);
    }

    public function test_it_rejects_non_existent_device(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:listen', [
            'device' => '/dev/ttyNONEXISTENT999',
        ]);

        $command->expectsOutputToContain('does not exist')
            ->assertExitCode(1);
    }

    public function test_it_validates_invalid_options(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:listen', [
            'device' => '/dev/ttyUSB0',
            '--baud' => 0,
        ]);

        $command->assertExitCode(1);
    }
}
