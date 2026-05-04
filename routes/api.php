<?php

declare(strict_types=1);

use Hamzi\PortFlow\Infrastructure\Http\Controllers\IngestController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('portflow.ingest_middleware', ['web']))
    ->post((string) config('portflow.ingest_path', '/portflow/ingest'), IngestController::class)
    ->name('portflow.ingest');
