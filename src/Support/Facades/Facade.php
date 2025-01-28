<?php

declare(strict_types=1);

namespace Excalibur\Support\Facades;

abstract class Facade
{
    /**
     * The resolved object instances.
     *
     * @var array<string, mixed>
     */
    protected static array $resolvedInstances = [];

    /**
     * Get the root object behind the facade.
     */
    abstract protected static function getFacadeAccessor(): string;

    /**
     * Get the resolved instance.
     */
    protected static function resolveFacadeInstance(string $name): mixed
    {
        if (isset(static::$resolvedInstances[$name])) {
            return static::$resolvedInstances[$name];
        }

        throw new \RuntimeException("A facade root has not been set for [{$name}].");
    }

    /**
     * Set the facade instance.
     */
    public static function setFacadeInstance(string $name, mixed $instance): void
    {
        static::$resolvedInstances[$name] = $instance;
    }

    /**
     * Handle dynamic, static calls to the object.
     * @param string $method
     * @param array<mixed, mixed> $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::resolveFacadeInstance(static::getFacadeAccessor());

        return $instance->$method(...$args);
    }

    /**
     * Clear all resolved instances.
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstances = [];
    }
}
