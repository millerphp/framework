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

    /**
     * @var array<string, string>
     */
    private array $wheres = [];

    /**
     * @var array<string, mixed>
     */
    private array $defaults = [];

    /**
     * Create a new route instance
     *
     * @param array<string>|string $methods HTTP methods this route responds to
     * @param string $uri The URI pattern for this route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler The route handler
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
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function get(string $uri, mixed $handler): self
    {
        return new self('GET', $uri, $handler);
    }

    /**
     * Create a new POST route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function post(string $uri, mixed $handler): self
    {
        return new self('POST', $uri, $handler);
    }

    /**
     * Create a new PUT route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function put(string $uri, mixed $handler): self
    {
        return new self('PUT', $uri, $handler);
    }

    /**
     * Create a new DELETE route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function delete(string $uri, mixed $handler): self
    {
        return new self('DELETE', $uri, $handler);
    }

    /**
     * Create a new PATCH route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function patch(string $uri, mixed $handler): self
    {
        return new self('PATCH', $uri, $handler);
    }

    /**
     * Create a new HEAD route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function head(string $uri, mixed $handler): self
    {
        return new self('HEAD', $uri, $handler);
    }

    /**
     * Create a new OPTIONS route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function options(string $uri, mixed $handler): self
    {
        return new self('OPTIONS', $uri, $handler);
    }

    /**
     * Create a new TRACE route
     * @param (callable(): mixed)|array{0: class-string, 1: string}|string $handler
     */
    public static function trace(string $uri, mixed $handler): self
    {
        return new self('TRACE', $uri, $handler);
    }

    /**
     * Create a route matching multiple methods
     * @param array<string> $methods
     * @param callable|string|array{0: class-string, 1: string} $handler
     */
    public static function match(array $methods, string $uri, callable|string|array $handler): self
    {
        return new self($methods, $uri, $handler);
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
     * @param array<string>|string $middleware
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
     * @return (callable(): mixed)|array{0: class-string, 1: string}|string
     */
    public function getHandler(): mixed
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
     * @param array<string, string>|string $name
     */
    public function where(array|string $name, ?string $pattern = null): static
    {
        if (is_array($name)) {
            $this->wheres = array_merge($this->wheres, $name);
        } else {
            $this->wheres[$name] = $pattern ?? '[^/]+';
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
     * @return array<string, string>
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * @return array<string, mixed>
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
                fn ($key) => is_string($key),
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
            function ($matches) {
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

        if (is_array($this->handler)) {
            /** @var mixed[] $handler */
            $handler = $this->handler;

            if (count($handler) !== 2) {
                throw new RouterException('Invalid controller array format. Expected [Controller::class, "method"]');
            }

            /** @var mixed $controller */
            $controller = $handler[0];
            /** @var mixed $method */
            $method = $handler[1];

            if (!is_string($controller) || !is_string($method)) {
                throw new RouterException('Invalid controller array format. Expected [Controller::class, "method"]');
            }

            if (!class_exists($controller)) {
                throw RouterException::controllerNotFound($controller);
            }

            $instance = new $controller();
            if (!method_exists($instance, $method)) {
                throw new RouterException(
                    sprintf('Method %s not found on controller %s', $method, $controller)
                );
            }

            return $instance->$method(...$this->parameters);
        }

        if (!is_string($this->handler)) {
            throw new RouterException('Invalid route handler');
        }

        // Handle "Controller@method" string format
        if (str_contains($this->handler, '@')) {
            [$controller, $method] = explode('@', $this->handler);
            if (!class_exists($controller)) {
                throw RouterException::controllerNotFound($controller);
            }
            $instance = new $controller();
            return $instance->$method(...$this->parameters);
        }

        // Handle invokable controller class
        if (!class_exists($this->handler)) {
            throw RouterException::controllerNotFound($this->handler);
        }
        $instance = new $this->handler();
        if (!is_callable($instance)) {
            throw RouterException::invalidController($this->handler);
        }
        return $instance(...$this->parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generateUrl(array $parameters = []): string
    {
        $uri = $this->uri;

        foreach ($parameters as $key => $value) {
            $pattern = "/\{" . preg_quote((string)$key, '/') . "\??}/";
            $replacement = is_scalar($value) ? (string)$value : '';
            $uri = (string)preg_replace($pattern, $replacement, $uri);
        }

        // Remove any remaining optional parameters
        $uri = (string)preg_replace('/\{[^}]+\?}/', '', $uri);

        return $uri;
    }
}
