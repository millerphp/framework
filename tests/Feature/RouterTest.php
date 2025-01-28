<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Fixtures\Controllers\TestController;
use Excalibur\Router\Router;
use Excalibur\Router\Exception\RouterException;
use Excalibur\Router\Route;

describe('Router', function () {
    it('handles basic routing', function () {
        $router = new Router();
        $router->get('/test', fn () => 'test result');

        expect($router->dispatch('/test', 'GET'))->toBe('test result');
    });

    it('handles route parameters', function () {
        $router = new Router();
        $router->get('/users/{id}', fn ($id) => "User $id");

        expect($router->dispatch('/users/123', 'GET'))->toBe('User 123');
    });

    it('throws 404 for non-existent routes', function () {
        $router = new Router();

        expect(fn () => $router->dispatch('/non-existent', 'GET'))
            ->toThrow(RouterException::class, 'No route found for GET /non-existent');
    });

    it('handles different HTTP methods', function () {
        $router = new Router();

        $router->get('/test', fn () => 'GET');
        $router->post('/test', fn () => 'POST');

        expect($router->dispatch('/test', 'GET'))->toBe('GET')
            ->and($router->dispatch('/test', 'POST'))->toBe('POST');
    });

    it('generates URLs for named routes', function () {
        $router = new Router();
        $route = $router->get('/users/{id}', fn () => 'user');

        // Register the route name after creation
        $route->name('users.show');

        expect($router->url('users.show', ['id' => 123]))
            ->toBe('/users/123');
    });

    it('handles route patterns', function () {
        $router = new Router();

        // Define the pattern first
        $router->pattern('id', '[0-9]+');

        // Create and configure the route
        $route = $router->get('/users/{id}', fn ($id) => $id)
            ->where('id', '[0-9]+');

        // Test valid numeric ID
        expect($router->dispatch('/users/123', 'GET'))->toBe('123');

        // Test invalid non-numeric ID - should throw exception
        expect(fn () => $router->dispatch('/users/abc', 'GET'))
            ->toThrow(RouterException::class, 'No route found for GET /users/abc');
    });

    it('handles controller classes', function () {
        $router = new Router();
        $router->get('/test', TestController::class);

        expect($router->dispatch('/test', 'GET'))
            ->toBe('invoked controller');
    });
});
