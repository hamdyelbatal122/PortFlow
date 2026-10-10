<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Unit;

use Hamzi\PortFlow\Exceptions\PortFlowException;
use Hamzi\PortFlow\Infrastructure\Printing\BladeEscPosRenderer;
use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

final class BladeEscPosRendererTest extends TestCase
{
    public function test_it_renders_lines_with_feeds_and_cut(): void
    {
        $mockView = $this->createMock(View::class);
        $mockView->method('render')->willReturn("RECEIPT TITLE\nItem 1  $10.00\nItem 2  $20.00");

        $mockViews = $this->createMock(Factory::class);
        $mockViews->method('make')->with('receipts.test', ['key' => 'val'])->willReturn($mockView);

        $renderer = new BladeEscPosRenderer($mockViews);
        $bytes = $renderer->render('receipts.test', ['key' => 'val']);

        $this->assertStringStartsWith("\x1B\x40", $bytes); // ESC @
        $this->assertStringContainsString("RECEIPT TITLE\n", $bytes);
        $this->assertStringContainsString("Item 1  $10.00\n", $bytes);
        $this->assertStringEndsWith("\x1D\x56\x00", $bytes); // GS V \x00 cut
    }

    public function test_it_wraps_render_exceptions_in_portflow_exception(): void
    {
        $mockViews = $this->createMock(Factory::class);
        $mockViews->method('make')->willThrowException(new \RuntimeException('View not found'));

        $renderer = new BladeEscPosRenderer($mockViews);

        $this->expectException(PortFlowException::class);
        $this->expectExceptionMessage('PortFlow failed to render ESC/POS view');

        $renderer->render('missing.view');
    }
}
