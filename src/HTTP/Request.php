<?php

declare(strict_types=1);

namespace Excalibur\HTTP;

class Request
{
    /**
     * The request data.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get a specific input value from the request.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get all input values from the request.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Check if a specific input value exists in the request.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get the request method (GET, POST, etc.).
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the request URI.
     */
    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    /**
     * Get a specific header from the request.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $_SERVER['HTTP_' . $key] ?? $default;
    }
} 