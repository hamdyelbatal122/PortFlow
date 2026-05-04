<?php

declare(strict_types=1);

namespace Hamzi\PortFlow\Application\Services;

use Hamzi\PortFlow\Domain\Contracts\SerialDriver;
use Hamzi\PortFlow\Exceptions\PortFlowException;
use Illuminate\Contracts\Container\Container;

final class DriverRegistry
{
    /**
     * @var array<string, string>
     */
    private array $drivers = [];

    public function __construct(private readonly Container $container)
    {
        foreach ((array) config('portflow.drivers', []) as $name => $class) {
            if (is_string($name) && is_string($class)) {
                $this->drivers[$name] = $class;
            }
        }
    }

    public function register(string $name, string $driverClass): void
    {
        $this->drivers[$name] = $driverClass;
    }

    public function resolve(string $name): SerialDriver
    {
        $className = $this->drivers[$name] ?? null;

        if ($className === null) {
            throw PortFlowException::driverNotFound($name);
        }

        /** @var SerialDriver $driver */
        $driver = $this->container->make($className);
        $driver->configure((array) config("portflow.driver_options.{$name}", []));

        return $driver;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->drivers;
    }
}
