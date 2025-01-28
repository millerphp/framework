<?php

declare(strict_types=1);

namespace Excalibur\Router;

class RouteCollection
{
    /**
     * Routes indexed by HTTP method
     * @var array<string, array<Route>>
     */
    private array $routes = [];

    /**
     * Named routes lookup
     * @var array<string, Route>
     */
    private array $namedRoutes = [];

    /**
     * Add a route to the collection
     */
    public function add(Route $route): void
    {
        foreach ($route->getMethods() as $method) {
            $this->routes[$method][] = $route;
        }

        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Get all routes for a given HTTP method
     * @return array<Route>
     */
    public function getRoutesByMethod(string $method): array
    {
        return $this->routes[$method] ?? [];
    }

    /**
     * Get a route by its name
     */
    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Check if a named route exists
     */
    public function hasNamedRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * Add a named route to the collection
     */
    public function addNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }
}
