<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Infrastructure\Printing;

use Hamzi\PortFlow\Exceptions\PortFlowException;
use Illuminate\Contracts\View\Factory;
use Throwable;

final class BladeEscPosRenderer
{
    public function __construct(private readonly Factory $views) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data = []): string
    {
        try {
            $content = trim($this->views->make($view, $data)->render());
        } catch (Throwable $e) {
            throw PortFlowException::printFailed($view, $e->getMessage(), $e);
        }

        $builder = new EscPosBuilder;

        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            $builder->text(rtrim((string) $line));
        }

        return $builder->feed(2)->cut()->bytes();
    }
}
