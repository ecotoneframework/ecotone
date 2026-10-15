<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Config\ModuleClassList;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class ServiceConfigurationModulePackagesTest extends TestCase
{
    public function test_every_package_except_test_is_enabled_when_enabled_module_package_names_were_not_defined(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults();

        $this->assertFalse($serviceConfiguration->areEnabledModulePackageNamesDefined());
        $this->assertSame(
            array_values(array_diff(ModuleClassList::packageNames(), [ModulePackageList::TEST_PACKAGE])),
            $serviceConfiguration->getEnabledModulePackageNames()
        );
    }

    public function test_loading_empty_list_of_packages_keeps_core_package(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnabledModulePackageNames([]);

        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::CORE_PACKAGE));
        $this->assertFalse($serviceConfiguration->isModulePackageEnabled(ModulePackageList::DBAL_PACKAGE));
        $this->assertFalse($serviceConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE));
    }

    public function test_loading_given_packages_together_with_core_package(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnabledModulePackageNames([ModulePackageList::DBAL_PACKAGE, ModulePackageList::AMQP_PACKAGE]);

        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::CORE_PACKAGE));
        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::DBAL_PACKAGE));
        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::AMQP_PACKAGE));
        $this->assertFalse($serviceConfiguration->isModulePackageEnabled(ModulePackageList::KAFKA_PACKAGE));
    }

    public function test_test_package_can_be_loaded_explicitly(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnabledModulePackageNames([ModulePackageList::TEST_PACKAGE]);

        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE));
        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::CORE_PACKAGE));
    }

    public function test_last_call_to_module_packages_wins(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnabledModulePackageNames([ModulePackageList::DBAL_PACKAGE])
            ->withEnabledModulePackageNames([ModulePackageList::AMQP_PACKAGE]);

        $this->assertFalse($serviceConfiguration->isModulePackageEnabled(ModulePackageList::DBAL_PACKAGE));
        $this->assertTrue($serviceConfiguration->isModulePackageEnabled(ModulePackageList::AMQP_PACKAGE));
    }
}
