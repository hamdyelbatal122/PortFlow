<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Feature;

use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Testing\PendingCommand;

final class SimulateCommandTest extends TestCase
{
    public function test_it_simulates_barcode_scan_successfully(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:simulate', [
            'driver' => 'barcode-line',
            'chunk' => "TEST_BARCODE_999\n",
        ]);

        $command->expectsOutputToContain('PortFlow Hardware Simulation')
            ->expectsOutputToContain('Frames Processed')
            ->expectsOutputToContain('TEST_BARCODE_999')
            ->assertExitCode(0);
    }

    public function test_it_outputs_json_simulation(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:simulate', [
            'driver' => 'raw-json',
            'chunk' => "{\"type\":\"test.ping\",\"status\":\"ok\"}\n",
            '--json' => true,
        ]);

        $command->assertExitCode(0);
    }

    public function test_it_handles_unknown_driver_gracefully(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:simulate', [
            'driver' => 'unknown-driver-xyz',
            'chunk' => "data\n",
        ]);

        $command->assertExitCode(1);
    }

    public function test_it_notifies_when_no_complete_frames_produced(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:simulate', [
            'driver' => 'barcode-line',
            'chunk' => 'partial_without_newline',
        ]);

        $command->expectsOutputToContain('No complete frames produced')
            ->assertExitCode(0);
    }
}
