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

    /**
     * @var array<string, string>
     */
    private array $patterns = [];

    public function __construct()
    {
        $this->routes = new RouteCollection();
        $this->compiler = new RouteCompiler();
        RouteFacade::setRouter($this);
    }

    /**
     * Add a GET route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public function get(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['GET'], $uri, $handler);
    }

    /**
     * Add a POST route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public function post(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['POST'], $uri, $handler);
    }

    /**
     * Add a PUT route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public function put(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['PUT'], $uri, $handler);
    }

    /**
     * Add a DELETE route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public function delete(string $uri, mixed $handler): Route
    {
        return $this->addRoute(['DELETE'], $uri, $handler);
    }

    /**
     * Add a route that responds to multiple HTTP methods
     * @param array<string> $methods
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public function match(array $methods, string $uri, mixed $handler): Route
    {
        return $this->addRoute($methods, $uri, $handler);
    }

    /**
     * Add a route to the routing table
     * @param array<string> $methods
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    protected function addRoute(array $methods, string $uri, mixed $handler): Route
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
        /** @var array<string, string|mixed> $group */
        $group = RouteFacade::getCurrentGroup();
        $prefix = '';

        if (isset($group['prefix'])) {
            $prefixValue = $group['prefix'];
            if (is_string($prefixValue)) {
                $prefix = trim($prefixValue, '/');
            }
        }

        $uri = trim($uri, '/');
        return $prefix ? "$prefix/$uri" : $uri;
    }

    /**
     * Process the handler with current group stack
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     * @return (callable(): mixed)|array{0: class-string, 1: string}|string
     */
    protected function processHandler(mixed $handler): mixed
    {
        if (!is_string($handler)) {
            return $handler;
        }

        /** @var array<string, string|mixed> $group */
        $group = RouteFacade::getCurrentGroup();

        if (!isset($group['namespace'])) {
            return $handler;
        }

        $namespaceValue = $group['namespace'];
        if (!is_string($namespaceValue)) {
            return $handler;
        }

        $namespace = trim($namespaceValue, '\\');
        return $namespace . '\\' . ltrim($handler, '\\');
    }

    /**
     * Convert URI pattern to regex
     */
    protected function convertUriToRegex(string $uri): string
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z]+)(\?)?\}/', function (array $matches) {
            $param = $matches[1];
            $optional = isset($matches[2]);

            if (str_contains($param, ':')) {
                [$param, $patternName] = explode(':', $param);
                $regex = isset($this->patterns[$patternName]) ? (string)$this->patterns[$patternName] : '[^/]+';
            } else {
                $regex = '[^/]+';
            }

            return $optional ? "(?:/(?P<$param>$regex))?" : "(?P<$param>$regex)";
        }, $uri);

        if ($pattern === null) {
            return '#^/#';
        }

        return '#^/' . str_replace('/', '\/', $pattern) . '$#';
    }

    /**
     * Extract named parameters from URI
     * @param array<string, mixed> $matches
     * @return array<string, mixed>
     */
    protected function extractNamedParameters(string $uri, array $matches): array
    {
        $parameters = [];
        /** @var array{0: string, 1: array<int, string>} $parameterNames */
        $parameterNames = [];
        preg_match_all('/\{([a-zA-Z]+)\}/', $uri, $parameterNames);

        foreach ($parameterNames[1] as $name) {
            $parameters[$name] = $matches[$name] ?? null;
        }

        return $parameters;
    }

    /**
     * Execute the route handler
     * @param array<string, mixed> $parameters
     * @throws RouterException
     */
    protected function executeHandler(callable|string $handler, array $parameters): mixed
    {
        if (is_callable($handler)) {
            return $handler(...array_values($parameters));
        }

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

    /**
     * Add a pattern for parameter matching
     */
    public function pattern(string $name, string $pattern): void
    {
        $this->compiler->addPattern($name, $pattern);
    }

    /**
     * Generate URL for named route
     * @param array<string, mixed> $parameters
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);

        if (!$route) {
            throw RouterException::namedRouteNotFound($name);
        }

        /** @var array<string, mixed> $parameters */
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
            /** @var array<string, string> $wheres */
            $wheres = $route->getWheres();
            $pattern = $this->compiler->compile($route->getUri(), $wheres);

            if (preg_match($pattern, $uri, $matches)) {
                /** @var array<string, string> $matches */
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
