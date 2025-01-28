<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Excalibur\Router\Route;
use Excalibur\Router\RouteCollection;
use Excalibur\Router\Facades\Route as RouteFacade;

describe('RouteCollection', function () {
    beforeEach(function () {
        $this->createRouter();
    });

    it('stores and retrieves routes by method', function () {
        $collection = new RouteCollection();
        $route = Route::get('/test', fn () => 'test');

        $collection->add($route);

        expect($collection->getRoutesByMethod('GET'))
            ->toHaveCount(1)
            ->sequence(
                fn ($route) => $route->toBeInstanceOf(Route::class)
            );
    });

    it('handles named routes', function () {
        $collection = RouteFacade::getRouter()->getRouteCollection();
        $route = Route::get('/test', fn () => 'test');

        $collection->add($route);
        $route->name('test.route');

        expect($collection->getByName('test.route'))
            ->toBeInstanceOf(Route::class)
            ->and($collection->hasNamedRoute('test.route'))->toBeTrue()
            ->and($collection->hasNamedRoute('non.existent'))->toBeFalse();
    });

    it('handles multiple routes with same URI but different methods', function () {
        $collection = new RouteCollection();

        $getRoute = Route::get('/test', fn () => 'GET');
        $postRoute = Route::post('/test', fn () => 'POST');

        $collection->add($getRoute);
        $collection->add($postRoute);

        expect($collection->getRoutesByMethod('GET'))->toHaveCount(1)
            ->and($collection->getRoutesByMethod('POST'))->toHaveCount(1)
            ->and($collection->getRoutesByMethod('PUT'))->toHaveCount(0);
    });

    it('returns null for non-existent named routes', function () {
        $collection = new RouteCollection();

        expect($collection->getByName('non.existent'))->toBeNull();
    });

    it('overwrites existing named routes', function () {
        $collection = new RouteCollection();

        $route1 = Route::get('/test1', fn () => 'test1')->name('test');
        $route2 = Route::get('/test2', fn () => 'test2')->name('test');

        $collection->add($route1);
        $collection->add($route2);

        expect($collection->getByName('test'))->toBe($route2);
    });
});
