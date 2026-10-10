<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Unit;

use Hamzi\PortFlow\PortFlowManager;
use Hamzi\PortFlow\Tests\TestCase;

final class PortFlowManagerTest extends TestCase
{
    public function test_health_returns_expected_metadata(): void
    {
        $manager = app(PortFlowManager::class);
        $health = $manager->health();

        $this->assertSame('ok', $health['status']);
        $this->assertContains('raw-json', $health['registered_drivers']);
        $this->assertArrayHasKey('cache_driver', $health);
        $this->assertArrayHasKey('queue_routing', $health);
    }

    public function test_it_registers_and_delegates_driver(): void
    {
        $manager = app(PortFlowManager::class);
        $manager->registerDriver('custom', 'CustomDriverClass');

        $health = $manager->health();
        $this->assertContains('custom', $health['registered_drivers']);
    }

    public function test_it_ingests_and_encodes_through_facade_and_manager(): void
    {
        $manager = app(PortFlowManager::class);

        $encoded = $manager->encode('barcode-line', ['barcode' => 'TEST_ITEM_123']);
        $this->assertStringContainsString('TEST_ITEM_123', $encoded);

        $frames = $manager->ingest('barcode-line', "TEST_ITEM_123\n");
        $this->assertCount(1, $frames);
        $this->assertSame('TEST_ITEM_123', $frames[0]->payload['barcode']);
    }
}
