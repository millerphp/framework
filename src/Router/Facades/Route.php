<?php

declare(strict_types=1);

namespace Excalibur\Router\Facades;

use Excalibur\Support\Facades\Facade;
use Excalibur\Router\Router;

/**
 * @method static \Excalibur\Router\Route get(string $uri, callable|string|array<string, mixed> $handler)
 * @method static \Excalibur\Router\Route post(string $uri, callable|string|array<string, mixed> $handler)
 * @method static \Excalibur\Router\Route put(string $uri, callable|string|array<string, mixed> $handler)
 * @method static \Excalibur\Router\Route delete(string $uri, callable|string|array<string, mixed> $handler)
 * @method static \Excalibur\Router\Route match(array<string> $methods, string $uri, callable|string|array<string, mixed> $handler)
 * @method static mixed dispatch(string $uri, string $method = 'GET')
 * @method static string url(string $name, array<string, mixed> $parameters = [])
 */
class Route extends Facade
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected static array $groupStack = [];

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }

    /**
     * Set the router instance.
     */
    public static function setRouter(Router $router): void
    {
        static::setFacadeInstance('router', $router);
    }

    /**
     * Get the router instance
     * @return \Excalibur\Router\Router
     */
    public static function getRouter(): \Excalibur\Router\Router
    {
        $instance = static::resolveFacadeInstance('router');
        assert($instance instanceof \Excalibur\Router\Router);
        return $instance;
    }

    /**
     * Push a route group onto the stack.
     *
     * @param array<string, mixed> $attributes
     */
    public static function pushGroup(array $attributes = []): void
    {
        static::$groupStack[] = $attributes;
    }

    /**
     * Pop a route group off the stack.
     */
    public static function popGroup(): void
    {
        array_pop(static::$groupStack);
    }

    /**
     * Get the current route group.
     *
     * @return array<string, mixed>
     */
    public static function getCurrentGroup(): array
    {
        /** @var array<string, mixed>|false $current */
        $current = end(static::$groupStack);
        return $current === false ? [] : $current;
    }
}
