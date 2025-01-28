<?php

namespace Tests\Unit\Router;

use Tests\TestCase;
use Excalibur\Router\Route;
use Excalibur\Router\RouteCollection;
use Excalibur\Router\Facades\Route as RouteFacade;

describe('RouteCollection', function() {
    beforeEach(function() {
        $this->createRouter();
    });

    it('stores and retrieves routes by method', function() {
        $collection = new RouteCollection();
        $route = Route::get('/test', fn() => 'test');
        
        $collection->add($route);
        
        expect($collection->getRoutesByMethod('GET'))
            ->toHaveCount(1)
            ->sequence(
                fn($route) => $route->toBeInstanceOf(Route::class)
            );
    });

    it('handles named routes', function() {
        $collection = RouteFacade::getRouter()->getRouteCollection();
        $route = Route::get('/test', fn() => 'test');
        
        $collection->add($route);
        $route->name('test.route');
        
        expect($collection->getByName('test.route'))
            ->toBeInstanceOf(Route::class)
            ->and($collection->hasNamedRoute('test.route'))->toBeTrue()
            ->and($collection->hasNamedRoute('non.existent'))->toBeFalse();
    });
}); 