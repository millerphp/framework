<?php

declare(strict_types=1);

namespace Excalibur\Router\Interfaces;

use Excalibur\Router\Exception\RouterException;
use Excalibur\Router\Route;

interface RouterInterface
{
    /**
     * Add a GET route
     * 
     * @param string $uri The route URI
     * @param callable|string $handler The route handler (callable or invokable class name)
     * @return Route
     */
    public function get(string $uri, callable|string $handler): Route;

    /**
     * Add a POST route
     * 
     * @param string $uri The route URI
     * @param callable|string $handler The route handler (callable or invokable class name)
     * @return Route
     */
    public function post(string $uri, callable|string $handler): Route;

    /**
     * Add a PUT route
     * 
     * @param string $uri The route URI
     * @param callable|string $handler The route handler (callable or invokable class name)
     * @return Route
     */
    public function put(string $uri, callable|string $handler): Route;

    /**
     * Add a DELETE route
     * 
     * @param string $uri The route URI
     * @param callable|string $handler The route handler (callable or invokable class name)
     * @return Route
     */
    public function delete(string $uri, callable|string $handler): Route;

    /**
     * Add a route that responds to multiple HTTP methods
     * 
     * @param array $methods HTTP methods
     * @param string $uri The route URI
     * @param callable|string $handler The route handler (callable or invokable class name)
     * @return Route
     */
    public function match(array $methods, string $uri, callable|string $handler): Route;

    /**
     * Dispatch the route and execute the appropriate handler
     * 
     * @param string $uri The URI to dispatch
     * @param string $method The HTTP method
     * @return mixed
     * @throws RouterException
     */
    public function dispatch(string $uri, string $method = 'GET'): mixed;
}
