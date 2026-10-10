<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Console\Commands;

use Hamzi\PortFlow\Application\Services\DriverRegistry;
use Hamzi\PortFlow\Domain\Contracts\SerialDriver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class HealthCheckCommand extends Command
{
    protected $signature = 'portflow:health
                            {--json : Output health check results as JSON}';

    protected $description = 'Run a fast health check on PortFlow drivers, cache, and queue readiness';

    public function handle(DriverRegistry $registry): int
    {
        $checks = [];
        $healthy = true;

        // Cache infrastructure check
        try {
            $testKey = 'portflow.health.check.'.bin2hex(random_bytes(4));
            Cache::put($testKey, 'ok', 5);
            $cacheWorking = Cache::get($testKey) === 'ok';
            Cache::forget($testKey);

            $checks['cache'] = [
                'status' => $cacheWorking ? 'ok' : 'fail',
                'driver' => (string) config('cache.default', 'unknown'),
            ];

            if (! $cacheWorking) {
                $healthy = false;
            }
        } catch (Throwable $e) {
            $healthy = false;
            $checks['cache'] = [
                'status' => 'fail',
                'error' => $e->getMessage(),
                'driver' => (string) config('cache.default', 'unknown'),
            ];
        }

        // Drivers check
        $driverErrors = [];
        $drivers = $registry->all();

        foreach ($drivers as $name => $class) {
            if (! class_exists($class)) {
                $driverErrors[$name] = "Class [{$class}] does not exist";
                $healthy = false;

                continue;
            }

            if (! is_a($class, SerialDriver::class, true)) {
                $driverErrors[$name] = "Class [{$class}] does not implement SerialDriver";
                $healthy = false;
            }
        }

        $checks['drivers'] = [
            'status' => $driverErrors === [] ? 'ok' : 'fail',
            'count' => count($drivers),
            'errors' => $driverErrors,
        ];

        // Mappings check
        $mappings = (array) config('portflow.mappings', []);
        $mappingErrors = [];

        foreach ($mappings as $index => $mapping) {
            if (! is_array($mapping)) {
                $mappingErrors[] = "Mapping index [{$index}] must be an array";
                $healthy = false;

                continue;
            }

            if (isset($mapping['event']) && is_string($mapping['event']) && ! class_exists($mapping['event'])) {
                $mappingErrors[] = "Event [{$mapping['event']}] does not exist";
                $healthy = false;
            }

            if (isset($mapping['model']) && is_string($mapping['model']) && ! class_exists($mapping['model'])) {
                $mappingErrors[] = "Model [{$mapping['model']}] does not exist";
                $healthy = false;
            }
        }

        $checks['mappings'] = [
            'status' => $mappingErrors === [] ? 'ok' : 'fail',
            'count' => count($mappings),
            'errors' => $mappingErrors,
        ];

        // Queue configuration check
        $queueRouting = (bool) config('portflow.queue_routing', false);
        $checks['queue'] = [
            'routing' => $queueRouting ? 'enabled' : 'disabled',
            'connection' => (string) (config('portflow.queue_connection') ?: config('queue.default', 'default')),
        ];

        if ($this->option('json')) {
            $this->output->writeln((string) json_encode([
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info('PortFlow Health Check');

        $this->components->twoColumnDetail(
            'Cache Connectivity',
            $checks['cache']['status'] === 'ok' ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'
        );

        $this->components->twoColumnDetail(
            'Registered Drivers ('.count($drivers).')',
            $checks['drivers']['status'] === 'ok' ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'
        );

        $this->components->twoColumnDetail(
            'Event Mappings ('.count($mappings).')',
            $checks['mappings']['status'] === 'ok' ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'
        );

        $this->components->twoColumnDetail(
            'Queue Routing',
            $queueRouting ? '<fg=cyan>ASYNC</>' : '<fg=gray>SYNC</>'
        );

        $this->newLine();

        if ($healthy) {
            $this->components->info('All PortFlow subsystems are healthy.');

            return self::SUCCESS;
        }

        $this->components->error('PortFlow detected one or more subsystem issues.');

        return self::FAILURE;
    }
}
