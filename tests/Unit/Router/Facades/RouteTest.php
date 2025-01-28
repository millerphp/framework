<?php

declare(strict_types=1);

namespace Tests\Unit\Router\Facades;

use Excalibur\Router\Router;
use Excalibur\Router\Facades\Route;

describe('Route Facade', function () {
    beforeEach(function () {
        $router = new Router();
        Route::setRouter($router);
    });

    it('creates routes through facade', function () {
        $route = Route::get('/test', fn () => 'test');

        expect($route)->toBeRoute()
            ->and($route->getUri())->toBe('/test');
    });

    it('handles route parameters through facade', function () {
        Route::get('/users/{id}', fn ($id) => "User $id");

        expect(Route::dispatch('/users/123', 'GET'))->toBe('User 123');
    });

    it('generates URLs through facade', function () {
        $route = Route::get('/users/{id}', fn () => 'user');

        // Register the route name after creation
        $route->name('users.show');

        expect($route)->toBeRoute()
            ->and(Route::url('users.show', ['id' => 123]))
            ->toBe('/users/123');
    });

    it('handles multiple HTTP methods through facade', function () {
        $route = Route::match(['GET', 'POST'], '/test', fn () => 'test');

        expect($route)->toBeRoute()
            ->and(Route::dispatch('/test', 'GET'))->toBe('test')
            ->and(Route::dispatch('/test', 'POST'))->toBe('test');
    });
});
