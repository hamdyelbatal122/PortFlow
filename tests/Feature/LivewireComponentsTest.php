<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Feature;

use Hamzi\PortFlow\Infrastructure\Livewire\PortFlowConnector;
use Hamzi\PortFlow\Infrastructure\Livewire\PortFlowStatus;
use Hamzi\PortFlow\Tests\TestCase;
use Livewire\Livewire;

final class LivewireComponentsTest extends TestCase
{
    public function test_portflow_status_component_renders_and_updates(): void
    {
        Livewire::test(PortFlowStatus::class)
            ->assertSee('Disconnected')
            ->dispatch('portflow-status-updated', connected: true, driver: 'barcode-line', baudRate: 115200, frames: 10, retryCount: 0)
            ->assertSet('connected', true)
            ->assertSet('driver', 'barcode-line')
            ->assertSet('baudRate', 115200)
            ->assertSet('frames', 10)
            ->assertSee('Connected')
            ->dispatch('portflow-frame-received', chunk: 'DATA_CHUNK', framesProcessed: 1)
            ->assertSet('lastChunk', 'DATA_CHUNK')
            ->assertSet('framesProcessed', 1)
            ->dispatch('portflow-error', message: 'Serial port closed unexpectedly')
            ->assertSet('lastError', 'Serial port closed unexpectedly');
    }

    public function test_portflow_connector_component_renders_with_defaults(): void
    {
        Livewire::test(PortFlowConnector::class)
            ->assertSet('connected', false)
            ->assertSet('driver', 'raw-json')
            ->assertSet('baudRate', 9600)
            ->call('setConnectionState', true, 'USB Serial Port (COM3)')
            ->assertSet('connected', true)
            ->assertSet('portLabel', 'USB Serial Port (COM3)');
    }
}
