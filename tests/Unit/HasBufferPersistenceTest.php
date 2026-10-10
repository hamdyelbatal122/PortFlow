<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Unit;

use Hamzi\PortFlow\Infrastructure\Drivers\Traits\HasBufferPersistence;
use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

final class HasBufferPersistenceTest extends TestCase
{
    private TestBufferPersistenceStub $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TestBufferPersistenceStub;
    }

    public function test_it_returns_empty_buffer_without_session_id(): void
    {
        [$buffer, $key] = $this->service->load([], 'test-driver');

        $this->assertSame('', $buffer);
        $this->assertNull($key);
    }

    public function test_it_persists_and_restores_plain_buffer(): void
    {
        $context = ['session_id' => 'session-alpha'];
        [$buffer, $key] = $this->service->load($context, 'test-driver');

        $this->assertSame('', $buffer);
        $this->assertNotNull($key);

        $this->service->store($key, 'buffered-content');

        [$restored, $restoredKey] = $this->service->load($context, 'test-driver');
        $this->assertSame('buffered-content', $restored);
        $this->assertSame($key, $restoredKey);
    }

    public function test_it_persists_and_restores_binary_safe_buffer(): void
    {
        $context = ['session_id' => 'session-binary'];
        [$buffer, $key] = $this->service->load($context, 'binary-driver', true);

        $binaryData = "\x00\xFF\xEF\x01";
        $this->service->store($key, $binaryData, true);

        [$restored] = $this->service->load($context, 'binary-driver', true);
        $this->assertSame($binaryData, $restored);
    }

    public function test_it_clears_buffer_properly(): void
    {
        $context = ['session_id' => 'session-to-clear'];
        $expectedKey = HasBufferPersistence::resolveCacheKey('test-driver', 'session-to-clear');

        Cache::put($expectedKey, 'data-to-forget', 300);
        $this->assertTrue(Cache::has($expectedKey));

        $this->service->clear($context, 'test-driver');
        $this->assertFalse(Cache::has($expectedKey));
    }
}

final class TestBufferPersistenceStub
{
    use HasBufferPersistence;

    /**
     * @param  array<string, mixed>  $context
     * @return array{0: string, 1: ?string}
     */
    public function load(array $context, string $driverName, bool $binarySafe = false): array
    {
        return $this->loadBuffer($context, $driverName, $binarySafe);
    }

    public function store(?string $cacheKey, string $buffer, bool $binarySafe = false, int $ttl = 300): void
    {
        $this->storeBuffer($cacheKey, $buffer, $binarySafe, $ttl);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function clear(array $context, string $driverName): void
    {
        $this->clearBuffer($context, $driverName);
    }
}
