<?php

declare(strict_types=1);

namespace Excalibur\HTTP\Facades;

use RuntimeException;
use Excalibur\Container\Container;

abstract class Facade
{
    protected static ?Container $container = null;

    abstract protected static function getFacadeAccessor(): string;

    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    public static function __callStatic(string $method, array $args)
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }

    protected static function getFacadeRoot()
    {
        if (static::$container === null) {
            throw new RuntimeException('Container has not been set.');
        }

        return static::$container->get(static::getFacadeAccessor());
    }
} 