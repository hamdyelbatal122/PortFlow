<?php

declare(strict_types=1);

namespace Hamzi\PortFlow;

use Hamzi\PortFlow\Application\Services\DriverRegistry;
use Hamzi\PortFlow\Application\Services\HardwareMessageService;
use Hamzi\PortFlow\Domain\DTO\SerialFrame;
use Hamzi\PortFlow\Infrastructure\Printing\BladeEscPosRenderer;

final class PortFlowManager
{
    public function __construct(
        private readonly DriverRegistry $registry,
        private readonly HardwareMessageService $messages,
        private readonly ?BladeEscPosRenderer $renderer = null,
    ) {}

    /** Register or override a driver class for the given name. */
    public function registerDriver(string $name, string $driverClass): void
    {
        $this->registry->register($name, $driverClass);
    }

    /**
     * Parse an inbound chunk through the named driver and route resulting frames.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, SerialFrame>
     */
    public function ingest(string $driver, string $chunk, array $context = []): array
    {
        return $this->messages->ingest($driver, $chunk, $context);
    }

    /**
     * Encode an outbound payload through the named driver.
     *
     * @param  array<int|string, mixed>|string  $payload
     */
    public function encode(string $driver, array|string $payload): string
    {
        return $this->messages->encode($driver, $payload);
    }

    /**
     * Render a Blade view to ESC/POS bytes for thermal printing.
     *
     * @param  array<string, mixed>  $data
     */
    public function print(string $view, array $data = []): string
    {
        $renderer = $this->renderer ?? app(BladeEscPosRenderer::class);

        return $renderer->render($view, $data);
    }

    /**
     * Return a health-check array with registered driver names and system state.
     *
     * @return array{status: string, default_driver: string, registered_drivers: list<string>, cache_driver: string, queue_routing: bool}
     */
    public function health(): array
    {
        return [
            'status' => 'ok',
            'default_driver' => (string) config('portflow.default_driver'),
            'registered_drivers' => array_keys($this->registry->all()),
            'cache_driver' => (string) config('cache.default', 'file'),
            'queue_routing' => (bool) config('portflow.queue_routing', false),
        ];
    }
}
