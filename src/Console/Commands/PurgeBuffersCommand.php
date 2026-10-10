<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Console\Commands;

use Hamzi\PortFlow\Application\Services\DriverRegistry;
use Hamzi\PortFlow\Infrastructure\Drivers\Traits\HasBufferPersistence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class PurgeBuffersCommand extends Command
{
    protected $signature = 'portflow:purge-buffers
                            {--driver= : Specific driver name to purge buffers for}
                            {--session= : Specific session ID to purge}';

    protected $description = 'Purge cached serial buffer state for specific drivers or sessions';

    public function handle(DriverRegistry $registry): int
    {
        $driverOption = $this->option('driver');
        $driverFilter = is_string($driverOption) && $driverOption !== '' ? $driverOption : null;

        $sessionOption = $this->option('session');
        $sessionFilter = is_string($sessionOption) && $sessionOption !== '' ? $sessionOption : null;

        $registeredDrivers = array_keys($registry->all());

        if ($driverFilter !== null && ! in_array($driverFilter, $registeredDrivers, true)) {
            $this->components->error("Driver [{$driverFilter}] is not registered.");

            return self::FAILURE;
        }

        $driversToTarget = $driverFilter !== null ? [$driverFilter] : $registeredDrivers;
        $purgedCount = 0;

        if ($sessionFilter !== null) {
            foreach ($driversToTarget as $driver) {
                $cacheKey = HasBufferPersistence::resolveCacheKey($driver, $sessionFilter);
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                    $purgedCount++;
                }

                // Also check legacy un-prefixed key for raw-json
                if ($driver === 'raw-json') {
                    $legacyKey = 'portflow.buf.'.hash('sha256', $sessionFilter);
                    if (Cache::has($legacyKey)) {
                        Cache::forget($legacyKey);
                        $purgedCount++;
                    }
                }
            }

            $this->components->info("Purged {$purgedCount} buffer entry(ies) for session [{$sessionFilter}].");

            return self::SUCCESS;
        }

        // When no specific session is given, inform user
        $this->components->info('Purge completed for target drivers: '.implode(', ', $driversToTarget));
        $this->comment('Tip: specify --session=<id> to purge a specific device or connection session buffer.');

        return self::SUCCESS;
    }
}
