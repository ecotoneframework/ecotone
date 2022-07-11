<?php

namespace Test\Ecotone\Laravel;

use Ecotone\Laravel\LaravelConfigurationVariableService;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

/**
 * @internal
 */
class LaravelConfigurationVariableServiceTest extends TestCase
{
    public function test_it_proxies_to_laravel_configuration_service()
    {
        $configurationService = new LaravelConfigurationVariableService();

        $this->assertFalse($configurationService->hasName('test.key'));
        $this->assertNull($configurationService->getByName('test.key'));

        Config::set('test.key', 'test-value');

        $this->assertTrue($configurationService->hasName('test.key'));
        $this->assertSame('test-value', $configurationService->getByName('test.key'));
    }
}
