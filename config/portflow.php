<?php

declare(strict_types=1);

use Hamzi\PortFlow\Domain\Events\ProductScanned;
use Hamzi\PortFlow\Infrastructure\Drivers\EscPosDriver;
use Hamzi\PortFlow\Infrastructure\Drivers\RawJsonDriver;
use Hamzi\PortFlow\Infrastructure\Drivers\Rs232Driver;

return [
    'default_driver' => env('PORTFLOW_DEFAULT_DRIVER', 'raw-json'),

    'ingest_path' => env('PORTFLOW_INGEST_PATH', '/portflow/ingest'),

    'ingest_middleware' => ['web'],

    'drivers' => [
        'raw-json' => RawJsonDriver::class,
        'escpos' => EscPosDriver::class,
        'rs232' => Rs232Driver::class,
    ],

    'driver_options' => [
        'raw-json' => [
            'delimiter' => "\n",
            'max_bytes' => 16384,
        ],
        'rs232' => [
            'delimiter' => "\n",
        ],
        'escpos' => [],
    ],

    'mappings' => [
        [
            'driver' => 'raw-json',
            'payload_field' => 'type',
            'equals' => 'barcode.scan',
            'event' => ProductScanned::class,
            'event_payload_field' => 'barcode',
        ],
        [
            'driver' => 'escpos',
            'event' => ProductScanned::class,
            'event_payload_field' => 'barcode',
        ],
    ],
];
