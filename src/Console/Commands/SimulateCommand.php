<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Console\Commands;

use Hamzi\PortFlow\PortFlowManager;
use Illuminate\Console\Command;
use Throwable;

final class SimulateCommand extends Command
{
    protected $signature = 'portflow:simulate
                            {driver : Driver name (e.g. barcode-line, raw-json, rfid-ascii, rs232)}
                            {chunk : Raw chunk string to simulate}
                            {--session= : Optional session identifier}
                            {--source=simulator : Source label}
                            {--json : Output result as JSON}';

    protected $description = 'Simulate hardware transmission and inspect resulting SerialFrame objects';

    public function handle(PortFlowManager $portflow): int
    {
        $driverArg = $this->argument('driver');
        $driver = is_string($driverArg) ? $driverArg : '';

        $chunkArg = $this->argument('chunk');
        $chunk = is_string($chunkArg) ? $chunkArg : '';

        // Unescape escaped newline sequences like \n or \r in CLI arguments
        $normalizedChunk = str_replace(['\r\n', '\r', '\n', '\t'], ["\r\n", "\r", "\n", "\t"], $chunk);

        $sessionOption = $this->option('session');
        $sessionId = is_string($sessionOption) && $sessionOption !== ''
            ? $sessionOption
            : 'sim:'.bin2hex(random_bytes(4));

        $sourceOption = $this->option('source');
        $source = is_string($sourceOption) && $sourceOption !== '' ? $sourceOption : 'simulator';

        $context = [
            'session_id' => $sessionId,
            'source' => $source,
            'simulated_at' => microtime(true),
        ];

        $startTime = microtime(true);

        try {
            $frames = $portflow->ingest($driver, $normalizedChunk, $context);
            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
        } catch (Throwable $e) {
            if ($this->option('json')) {
                $this->output->writeln((string) json_encode([
                    'ok' => false,
                    'driver' => $driver,
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::FAILURE;
            }

            $this->components->error("Simulation failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $serializedFrames = array_map(static fn ($frame) => $frame->toArray(), $frames);

            $this->output->writeln((string) json_encode([
                'ok' => true,
                'driver' => $driver,
                'frames_count' => count($frames),
                'elapsed_ms' => $elapsedMs,
                'frames' => $serializedFrames,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->components->info("PortFlow Hardware Simulation [{$driver}]");
        $this->components->twoColumnDetail('Frames Processed', (string) count($frames));
        $this->components->twoColumnDetail('Elapsed Time', "{$elapsedMs} ms");
        $this->components->twoColumnDetail('Session ID', $sessionId);
        $this->newLine();

        if ($frames === []) {
            $this->comment('No complete frames produced. Data may be buffered waiting for delimiter.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($frames as $index => $frame) {
            $rows[] = [
                $index + 1,
                $frame->driver,
                (string) json_encode($frame->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $frame->receivedAt->format('Y-m-d H:i:s'),
            ];
        }

        $this->table(['#', 'Driver', 'Payload', 'Timestamp'], $rows);

        return self::SUCCESS;
    }
}
