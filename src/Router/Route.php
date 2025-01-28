<?php

declare(strict_types=1);

namespace Excalibur\Router;

use Excalibur\Router\Exception\RouterException;

class Route
{
    /**
     * HTTP methods this route responds to
     * @var array<string>
     */
    private array $methods;

    /**
     * Route name for reverse routing
     */
    private ?string $name = null;

    /**
     * Route middleware
     * @var array<string>
     */
    private array $middleware = [];

    /**
     * Route parameters from URI matching
     * @var array<string, string>
     */
    private array $parameters = [];

    private array $wheres = [];
    private array $defaults = [];
    private bool $optional = false;

    /**
     * Create a new route instance
     * 
     * @param array|string $methods HTTP methods this route responds to
     * @param string $uri The URI pattern for this route
     * @param callable|string|array $handler The route handler
     */
    public function __construct(
        array|string $methods,
        private readonly string $uri,
        private readonly mixed $handler
    ) {
        $this->methods = (array)$methods;
    }

    /**
     * Create a new GET route
     */
    public static function get(string $uri, callable|string|array $handler): static
    {
        return new static('GET', $uri, $handler);
    }

    /**
     * Create a new POST route
     */
    public static function post(string $uri, callable|string|array $handler): static
    {
        return new static('POST', $uri, $handler);
    }

    /**
     * Create a new PUT route
     */
    public static function put(string $uri, callable|string|array $handler): static
    {
        return new static('PUT', $uri, $handler);
    }

    /**
     * Create a new DELETE route
     */
    public static function delete(string $uri, callable|string|array $handler): static
    {
        return new static('DELETE', $uri, $handler);
    }

    /**
     * Create a route matching multiple methods
     */
    public static function match(array $methods, string $uri, callable|string $handler): static
    {
        return new static($methods[0], $uri, $handler);
    }

    /**
     * Name the route
     */
    public function name(string $name): static
    {
        $this->name = $name;
        
        // Get the router instance from the facade and register this route
        Facades\Route::getRouter()->getRouteCollection()->addNamedRoute($name, $this);
        
        return $this;
    }

    /**
     * Add middleware to the route
     */
    public function middleware(array|string $middleware): static
    {
        $this->middleware = array_merge(
            $this->middleware,
            (array)$middleware
        );
        return $this;
    }

    /**
     * Get the route's HTTP methods
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the route's URI pattern
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route's handler
     * @return callable|string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get the route's name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the route's middleware
     * @return array<string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the route parameters
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set the route parameters
     * @param array<string, string> $parameters
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Add a constraint for a parameter
     */
    public function where(string|array $name, ?string $pattern = null): static
    {
        if (is_array($name)) {
            $this->wheres = array_merge($this->wheres, $name);
        } else {
            $this->wheres[$name] = $pattern;
        }
        
        return $this;
    }

    /**
     * Set a default value for a parameter
     */
    public function defaults(string $parameter, mixed $value): static
    {
        $this->defaults[$parameter] = $value;
        return $this;
    }

    /**
     * Get parameter constraints
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Get parameter defaults
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function matches(string $uri, string $method): bool
    {
        // First check if the method matches
        if (!in_array($method, $this->methods)) {
            return false;
        }

        // Get the pattern from the router's compiler
        $pattern = Facades\Route::getRouter()->getRouteCompiler()->compile($this->uri, $this->wheres);
        
        if (preg_match($pattern, $uri, $matches)) {
            $this->parameters = array_filter(
                $matches, 
                fn($key) => is_string($key), 
                ARRAY_FILTER_USE_KEY
            );
            return true;
        }

        return false;
    }

    protected function getRoutePattern(): string
    {
        $pattern = $this->uri;
        
        // Replace named parameters with regex patterns
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_-]*)\??}/',
            function($matches) {
                $name = $matches[1];
                $pattern = $this->wheres[$name] ?? '[^/]+';
                $optional = str_ends_with($matches[0], '?}');
                return sprintf('(?P<%s>%s)%s', $name, $pattern, $optional ? '?' : '');
            },
            $pattern
        );
        
        return '#^' . $pattern . '$#';
    }

    /**
     * Execute the route handler with parameters
     * @throws RouterException
     */
    public function execute(): mixed
    {
        if (is_callable($this->handler)) {
            return call_user_func_array($this->handler, $this->parameters);
        }

        if (is_string($this->handler) && str_contains($this->handler, '@')) {
            [$controller, $method] = explode('@', $this->handler);
            if (!class_exists($controller)) {
                throw RouterException::controllerNotFound($controller);
            }
            $instance = new $controller();
            return $instance->$method(...$this->parameters);
        }

        if (is_string($this->handler)) {
            if (!class_exists($this->handler)) {
                throw RouterException::controllerNotFound($this->handler);
            }
            $instance = new $this->handler();
            if (!is_callable($instance)) {
                throw RouterException::invalidController($this->handler);
            }
            return $instance(...$this->parameters);
        }

        throw new RouterException('Invalid route handler');
    }

    public function generateUrl(array $parameters = []): string
    {
        $uri = $this->uri;
        
        foreach ($parameters as $key => $value) {
            $uri = preg_replace("/\{" . $key . "\??}/", (string)$value, $uri);
        }
        
        // Remove any remaining optional parameters
        $uri = preg_replace('/\{[^}]+\?}/', '', $uri);
        
        return $uri;
    }
} 