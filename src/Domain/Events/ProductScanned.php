<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Domain\Events;

final class ProductScanned
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $barcode,
        public readonly array $context = [],
    ) {}
}
