<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Feature;

use Hamzi\PortFlow\Infrastructure\Drivers\Traits\HasBufferPersistence;
use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\PendingCommand;

final class PurgeBuffersCommandTest extends TestCase
{
    public function test_it_purges_specific_session_buffer(): void
    {
        $sessionId = 'test-session-123';
        $cacheKey = HasBufferPersistence::resolveCacheKey('barcode-line', $sessionId);

        Cache::put($cacheKey, 'pending_buffer_bytes', 300);
        $this->assertTrue(Cache::has($cacheKey));

        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:purge-buffers', [
            '--driver' => 'barcode-line',
            '--session' => $sessionId,
        ]);

        $command->expectsOutputToContain('Purged 1 buffer entry')
            ->assertExitCode(0)
            ->run();

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_it_fails_when_driver_is_not_registered(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:purge-buffers', [
            '--driver' => 'non-existent-driver',
        ]);

        $command->assertExitCode(1);
    }

    public function test_it_reports_general_purge_status_without_session(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:purge-buffers');

        $command->expectsOutputToContain('Purge completed for target drivers')
            ->assertExitCode(0);
    }
}
