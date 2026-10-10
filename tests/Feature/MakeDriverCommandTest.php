<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Tests\Feature;

use Hamzi\PortFlow\Tests\TestCase;
use Illuminate\Testing\PendingCommand;

final class MakeDriverCommandTest extends TestCase
{
    private string $generatedFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generatedFile = app_path('SerialDrivers/CustomScaleDriver.php');
        if (file_exists($this->generatedFile)) {
            unlink($this->generatedFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->generatedFile)) {
            unlink($this->generatedFile);
        }
        $dir = app_path('SerialDrivers');
        if (is_dir($dir) && count(scandir($dir) ?: []) === 2) {
            rmdir($dir);
        }
        parent::tearDown();
    }

    public function test_it_creates_driver_class_successfully(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:make-driver', [
            'name' => 'CustomScale',
        ]);

        $command->expectsOutputToContain('Driver [CustomScaleDriver] created successfully')
            ->expectsOutputToContain('Register the driver in')
            ->assertExitCode(0)
            ->run();

        $this->assertFileExists($this->generatedFile);
        $content = (string) file_get_contents($this->generatedFile);
        $this->assertStringContainsString('class CustomScaleDriver implements SerialDriver', $content);
        $this->assertStringContainsString("return 'custom-scale';", $content);
    }

    public function test_it_fails_when_driver_file_already_exists(): void
    {
        $dir = dirname($this->generatedFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->generatedFile, '<?php // existing');

        /** @var PendingCommand $command */
        $command = $this->artisan('portflow:make-driver', [
            'name' => 'CustomScale',
        ]);

        $command->expectsOutputToContain('already exists')
            ->assertExitCode(1);
    }
}
