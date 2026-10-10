<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Infrastructure\Drivers;

use Hamzi\PortFlow\Domain\Contracts\SerialDriver;
use Hamzi\PortFlow\Domain\DTO\SerialFrame;
use Hamzi\PortFlow\Domain\Services\IoTFrameBuffer;
use Hamzi\PortFlow\Infrastructure\Drivers\Traits\HasBufferPersistence;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class RawJsonDriver implements SerialDriver
{
    use HasBufferPersistence;

    private IoTFrameBuffer $buffer;

    public function __construct()
    {
        $this->buffer = new IoTFrameBuffer;
    }

    public function name(): string
    {
        return 'raw-json';
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function configure(array $options = []): void
    {
        $delimiter = (string) ($options['delimiter'] ?? "\n");
        $maxBytes = (int) ($options['max_bytes'] ?? 16384);

        $this->buffer = new IoTFrameBuffer($delimiter, $maxBytes);
    }

    /**
     * @param  array<int|string, mixed>|string  $payload
     */
    public function encodeOutbound(array|string $payload): string
    {
        if (is_string($payload)) {
            return $payload;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * Parses inbound chunks.
     *
     * If `context['session_id']` is present the buffer state is loaded from
     * the Laravel cache before parsing and saved back afterwards, so partial
     * packets are correctly reassembled across separate HTTP requests.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, SerialFrame>
     */
    public function parseInbound(string $chunk, array $context = []): array
    {
        [$bufferState, $cacheKey] = $this->loadBuffer($context, 'raw-json');

        if ($bufferState === '' && $cacheKey !== null && isset($context['session_id'])) {
            $legacyKey = 'portflow.buf.'.hash('sha256', (string) $context['session_id']);
            $legacyState = (string) Cache::get($legacyKey, '');
            if ($legacyState !== '') {
                $bufferState = $legacyState;
            }
        }

        if ($cacheKey !== null) {
            $this->buffer->setState($bufferState);
        }

        $frames = [];

        foreach ($this->buffer->push($chunk) as $packet) {
            try {
                $decoded = json_decode($packet, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decoded)) {
                    $decoded = ['raw' => $packet];
                }
            } catch (Throwable) {
                $decoded = ['raw' => $packet];
            }

            $frames[] = SerialFrame::now($this->name(), $decoded, $context);
        }

        if ($cacheKey !== null) {
            $this->storeBuffer($cacheKey, $this->buffer->getState());
        }

        return $frames;
    }
}
