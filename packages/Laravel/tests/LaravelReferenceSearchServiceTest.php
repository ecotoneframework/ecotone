<?php

namespace Test\Ecotone\Laravel;

use Ecotone\Laravel\LaravelReferenceSearchService;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Testbench\TestCase;

/**
 * @internal
 */
class LaravelReferenceSearchServiceTest extends TestCase
{
    public function test_it_returns_items_from_laravel_container()
    {
        $mockContainer = $this->getMockBuilder(Application::class)->getMock();
        $mockContainer
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($this->app->get('config'));

        $service = new LaravelReferenceSearchService($mockContainer);
        $service->get('config');
    }

    public function test_it_checks_for_items_in_the_laravel_container()
    {
        $mockContainer = $this->getMockBuilder(Application::class)->getMock();
        $mockContainer
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn($this->app->has('config'));

        $service = new LaravelReferenceSearchService($mockContainer);
        $service->has('config');
    }

    public function test_it_resolves_items_from_the_laravel_container()
    {
        $mockContainer = $this->getMockBuilder(Application::class)->getMock();
        $mockContainer
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn(
                $this->app->has('config')
            );

        $service = new LaravelReferenceSearchService($mockContainer);
        $service->resolve('config');
    }
}
