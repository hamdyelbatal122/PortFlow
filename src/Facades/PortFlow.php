<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Facades;

use Illuminate\Support\Facades\Facade;

final class PortFlow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'portflow';
    }
}
