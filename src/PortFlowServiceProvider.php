<?php

declare(strict_types=1);

namespace Hamzi\PortFlow;

use Hamzi\PortFlow\Application\Services\DriverRegistry;
use Hamzi\PortFlow\Application\Services\HardwareMessageService;
use Hamzi\PortFlow\Application\Services\MessageRouter;
use Hamzi\PortFlow\Infrastructure\Livewire\PortFlowConnector;
use Hamzi\PortFlow\Infrastructure\Livewire\PortFlowStatus;
use Hamzi\PortFlow\Infrastructure\Printing\BladeEscPosRenderer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class PortFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/portflow.php', 'portflow');

        $this->app->singleton(DriverRegistry::class, fn ($app) => new DriverRegistry($app));
        $this->app->singleton(MessageRouter::class, fn ($app) => new MessageRouter($app['events']));
        $this->app->singleton(HardwareMessageService::class, fn ($app) => new HardwareMessageService(
            $app->make(DriverRegistry::class),
            $app->make(MessageRouter::class),
        ));
        $this->app->singleton(BladeEscPosRenderer::class, fn ($app) => new BladeEscPosRenderer($app['view']));
        $this->app->singleton('portflow', fn ($app) => new PortFlowManager(
            $app->make(DriverRegistry::class),
            $app->make(HardwareMessageService::class),
        ));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'portflow');

        $this->publishes([
            __DIR__.'/../config/portflow.php' => config_path('portflow.php'),
        ], 'portflow-config');

        $this->publishes([
            __DIR__.'/../resources/js/portflow-serial.js' => public_path('vendor/portflow/portflow-serial.js'),
        ], 'portflow-assets');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'portflow');

        Livewire::component('portflow-connector', PortFlowConnector::class);
        Livewire::component('portflow-status', PortFlowStatus::class);
    }
}
