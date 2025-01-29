<?php

declare(strict_types=1);

namespace Excalibur\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use ReflectionException;
use RuntimeException;

class Container
{
    /**
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    public function bind(string $abstract, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete);
    }

    public function get(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;
        $object = $this->resolve($concrete);

        if (isset($this->bindings[$abstract])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    public function call(mixed $callback, array $parameters = [])
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback);
            $callback = [$this->get($class), $method];
        }

        $reflector = is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);

        $dependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            $dependencies[] = $this->resolveDependency($parameter, $parameters);
        }

        return $reflector->invokeArgs(
            is_array($callback) ? $callback[0] : null,
            $dependencies
        );
    }

    private function resolve(mixed $concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new RuntimeException("Cannot resolve class {$concrete}");
        }

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $dependencies[] = $this->resolveDependency($parameter);
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    private function resolveDependency(ReflectionParameter $parameter, array $parameters = [])
    {
        $name = $parameter->getName();
        
        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        if ($parameter->getType() && !$parameter->getType()->isBuiltin()) {
            $typeName = $parameter->getType()->getName();
            return $this->get($typeName);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new RuntimeException("Cannot resolve dependency {$name}");
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
} 