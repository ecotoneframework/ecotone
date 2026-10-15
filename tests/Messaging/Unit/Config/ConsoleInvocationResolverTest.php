<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Config\ConsoleInvocationResolver;
use Ecotone\Messaging\Config\ModuleClassList;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class ConsoleInvocationResolverTest extends TestCase
{
    public function test_resolves_no_console_prefix_when_no_framework_integration_package_is_loaded(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnabledModulePackageNames([ModulePackageList::DBAL_PACKAGE]);

        $this->assertNull(ConsoleInvocationResolver::resolveConsolePrefix($serviceConfiguration));
    }

    public function test_resolves_no_console_prefix_when_a_framework_package_is_enabled_but_not_installed(): void
    {
        foreach (ModuleClassList::forPackage(ModulePackageList::SYMFONY_PACKAGE) as $moduleClass) {
            if (class_exists($moduleClass) || interface_exists($moduleClass)) {
                $this->markTestSkipped('The Symfony integration is installed here, so the enabled-but-not-installed branch cannot be reached. Monorepo\CrossModuleTests\Tests\ConsoleInvocationPrefixTest covers every installed case.');
            }
        }

        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnabledModulePackageNames([ModulePackageList::SYMFONY_PACKAGE]);

        $this->assertNull(ConsoleInvocationResolver::resolveConsolePrefix($serviceConfiguration));
    }
}
