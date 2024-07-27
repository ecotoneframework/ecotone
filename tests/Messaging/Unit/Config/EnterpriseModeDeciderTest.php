<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\EnterpriseMode\EnterpriseClassInterface;
use Test\Ecotone\Messaging\Fixture\EnterpriseMode\EnterpriseMethodInterface;
use Test\Ecotone\Messaging\Fixture\EnterpriseMode\StandardInterface;

/**
 * @internal
 */
final class EnterpriseModeDeciderTest extends TestCase
{
    public function test_it_should_return_true_when_enterprise_mode_is_default(): void
    {
        $enterpriseModeDecider = new LicenceDecider(true);

        $this->assertTrue($enterpriseModeDecider->hasEnterpriseLicence());
    }

    public function test_when_enterprise_mode_is_not_default(): void
    {
        $enterpriseModeDecider = new LicenceDecider(false);

        $this->assertFalse($enterpriseModeDecider->hasEnterpriseLicence());
    }

    public function test_when_and_interface_to_call_has_no_enterprise_annotation(): void
    {
        $enterpriseModeDecider = new LicenceDecider(true);

        $this->assertFalse($enterpriseModeDecider->isEnabledSpecificallyFor(
            InterfaceToCall::create(StandardInterface::class, 'execute')
        ));
    }

    public function test_when_and_interface_to_call_has_no_enterprise_annotation_and_enterprise_mode_is_disabled(): void
    {
        $enterpriseModeDecider = new LicenceDecider(false);

        $this->assertFalse($enterpriseModeDecider->isEnabledSpecificallyFor(
            InterfaceToCall::create(StandardInterface::class, 'execute')
        ));
    }

    public function test_when_interface_to_call_has_enterprise_annotation_on_method_level(): void
    {
        $enterpriseModeDecider = new LicenceDecider(true);

        $this->assertTrue($enterpriseModeDecider->isEnabledSpecificallyFor(
            InterfaceToCall::create(EnterpriseMethodInterface::class, 'execute')
        ));
    }

    public function test_when_interface_to_call_has_enterprise_annotation_on_method_level_yet_enterprise_mode_is_disabled(): void
    {
        $enterpriseModeDecider = new LicenceDecider(false);

        $this->assertFalse($enterpriseModeDecider->isEnabledSpecificallyFor(
            InterfaceToCall::create(EnterpriseMethodInterface::class, 'execute')
        ));
    }

    public function test_when_class_definition_has_enterprise_annotation_on_method_level(): void
    {
        $enterpriseModeDecider = new LicenceDecider(true);

        $this->assertFalse($enterpriseModeDecider->isEnabledSpecificallyFor(
            ClassDefinition::createFor(TypeDescriptor::create(EnterpriseMethodInterface::class))
        ));
    }

    public function test_when_class_definition_has_enterprise_annotation_on_class_level(): void
    {
        $enterpriseModeDecider = new LicenceDecider(true);

        $this->assertTrue($enterpriseModeDecider->isEnabledSpecificallyFor(
            ClassDefinition::createFor(TypeDescriptor::create(EnterpriseClassInterface::class))
        ));
    }

    public function test_when_interface_to_call_has_enterprise_annotation_on_class_level(): void
    {
        $enterpriseModeDecider = new LicenceDecider(true);

        $this->assertTrue($enterpriseModeDecider->isEnabledSpecificallyFor(
            InterfaceToCall::create(EnterpriseClassInterface::class, 'execute')
        ));
    }
}
