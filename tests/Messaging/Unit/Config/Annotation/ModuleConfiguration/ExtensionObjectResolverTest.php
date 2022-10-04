<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Dto\OrderExample;
use Test\Ecotone\Messaging\Fixture\Dto\WithCustomer\Customer;

/**
 * @internal
 */
final class ExtensionObjectResolverTest extends TestCase
{
    public function test_should_resolve_config_class()
    {
        $resolvedObject = new stdClass();

        $this->assertEquals(
            $resolvedObject,
            ExtensionObjectResolver::resolveUnique(stdClass::class, [$resolvedObject, OrderExample::createFromId(1)], new Customer('some', '', ''))
        );
    }

    public function test_should_make_use_of_default_if_can_not_resolve()
    {
        $default = new Customer('some', '', '');

        $this->assertEquals(
            $default,
            ExtensionObjectResolver::resolveUnique(stdClass::class, [], $default)
        );
    }

    public function test_should_throw_exception_when_unique_resolved_object_is_registered_twice()
    {
        $this->expectException(InvalidArgumentException::class);

        ExtensionObjectResolver::resolveUnique(stdClass::class, [new stdClass(), new stdClass()], new Customer('some', '', ''));
    }

    public function test_resolving_object()
    {
        $data = [new stdClass(), new stdClass()];

        $this->assertEquals(
            $data,
            ExtensionObjectResolver::resolve(stdClass::class, $data)
        );
    }

    public function test_resolving_object_when_not_found()
    {
        $this->assertEquals(
            [],
            ExtensionObjectResolver::resolve(Customer::class, [new stdClass(), new stdClass()])
        );
    }
}
