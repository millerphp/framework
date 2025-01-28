<?php

declare(strict_types=1);

namespace Excalibur\Router;

use Excalibur\Router\Exception\RouterException;
use Excalibur\Router\Interfaces\RouterInterface;
use Excalibur\Router\Facades\Route as RouteFacade;

class Router implements RouterInterface
{
    private RouteCollection $routes;
    private RouteCompiler $compiler;

    public function __construct()
    {
        $this->routes = new RouteCollection();
        $this->compiler = new RouteCompiler();
        RouteFacade::setRouter($this);
    }

    /**
     * Add a GET route
     */
    public function get(string $uri, callable|string|array $handler): Route
    {
        return $this->addRoute(['GET'], $uri, $handler);
    }

    /**
     * Add a POST route
     */
    public function post(string $uri, callable|string|array $handler): Route
    {
        return $this->addRoute(['POST'], $uri, $handler);
    }

    /**
     * Add a PUT route
     */
    public function put(string $uri, callable|string|array $handler): Route
    {
        return $this->addRoute(['PUT'], $uri, $handler);
    }

    /**
     * Add a DELETE route
     */
    public function delete(string $uri, callable|string|array $handler): Route
    {
        return $this->addRoute(['DELETE'], $uri, $handler);
    }

    /**
     * Add a route that responds to multiple HTTP methods
     */
    public function match(array $methods, string $uri, callable|string|array $handler): Route
    {
        return $this->addRoute($methods, $uri, $handler);
    }

    /**
     * Add a route to the routing table
     */
    protected function addRoute(array $methods, string $uri, callable|string|array $handler): Route
    {
        $uri = $this->normalizeUri($uri);
        $route = new Route($methods, $uri, $handler);
        $this->routes->add($route);
        return $route;
    }

    /**
     * Process the URI with current group stack
     */
    protected function processUri(string $uri): string
    {
        $group = RouteFacade::getCurrentGroup();
        $prefix = $group['prefix'] ?? '';
        
        return $prefix ? trim($prefix, '/') . '/' . trim($uri, '/') : trim($uri, '/');
    }

    /**
     * Process the handler with current group stack
     */
    protected function processHandler(callable|string|array $handler): callable|string
    {
        $group = RouteFacade::getCurrentGroup();
        
        if (is_string($handler) && isset($group['namespace'])) {
            return $group['namespace'] . '\\' . $handler;
        }

        if (is_array($handler) && isset($group['namespace'])) {
            return [$group['namespace'] . '\\' . $handler[0], $handler[1]];
        }

        return $handler;
    }

    /**
     * Convert URI pattern to regex
     */
    protected function convertUriToRegex(string $uri): string
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z]+)(\?)?\}/', function($matches) {
            $param = $matches[1];
            $optional = isset($matches[2]) && $matches[2] === '?';
            
            if (str_contains($param, ':')) {
                [$param, $pattern] = explode(':', $param);
                $regex = $this->patterns[$pattern] ?? '[^/]+';
            } else {
                $regex = '[^/]+';
            }
            
            return $optional ? "(?:/(?P<$param>$regex))?" : "(?P<$param>$regex)";
        }, $uri);
        
        return '#^/' . str_replace('/', '\/', $pattern) . '$#';
    }

    /**
     * Extract named parameters from URI
     */
    protected function extractNamedParameters(string $uri, array $matches): array
    {
        $parameters = [];
        preg_match_all('/\{([a-zA-Z]+)\}/', $uri, $parameterNames);
        
        foreach ($parameterNames[1] as $index => $name) {
            $parameters[$name] = $matches[$index] ?? null;
        }

        return $parameters;
    }

    /**
     * Execute the route handler
     * 
     * @throws RouterException
     */
    protected function executeHandler(callable|string $handler, array $parameters): mixed
    {
        if (is_callable($handler)) {
            return $handler(...array_values($parameters));
        }

        // Handle string class name for invokable controller
        if (is_string($handler)) {
            if (!class_exists($handler)) {
                throw new RouterException("Controller class {$handler} not found");
            }

            $controller = new $handler();
            
            if (!is_callable($controller)) {
                throw new RouterException(
                    "Controller class {$handler} must be invokable (implement __invoke method)"
                );
            }

            return $controller(...array_values($parameters));
        }

        throw new RouterException('Invalid route handler');
    }

    /**
     * Add a pattern for parameter matching
     */
    public function pattern(string $name, string $pattern): void
    {
        $this->compiler->addPattern($name, $pattern);
    }

    /**
     * Generate URL for named route
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);
        
        if (!$route) {
            throw RouterException::namedRouteNotFound($name);
        }

        return $this->normalizeUri($route->generateUrl($parameters));
    }

    protected function normalizeUri(string $uri): string
    {
        return '/' . trim($uri, '/');
    }

    /**
     * Get a route by its name
     */
    public function getRouteByName(string $name): ?Route
    {
        return $this->routes->getByName($name);
    }

    /**
     * Dispatch the route and execute the appropriate handler
     * 
     * @throws RouterException
     */
    public function dispatch(string $uri, string $method = 'GET'): mixed
    {
        $uri = $this->normalizeUri($uri);
        $route = $this->findRoute($uri, $method);
        
        if ($route === null) {
            throw RouterException::routeNotFound($method, $uri);
        }

        return $route->execute();
    }

    /**
     * Find a matching route for the given URI and method
     */
    protected function findRoute(string $uri, string $method): ?Route
    {
        foreach ($this->routes->getRoutesByMethod($method) as $route) {
            $pattern = $this->compiler->compile($route->getUri(), $route->getWheres());
            
            if (preg_match($pattern, $uri, $matches)) {
                $parameters = $this->compiler->extractParameters($route->getUri(), $matches);
                $route->setParameters($parameters);
                return $route;
            }
        }

        return null;
    }

    /**
     * Get the route collection
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Get the route compiler
     */
    public function getRouteCompiler(): RouteCompiler
    {
        return $this->compiler;
    }
}
