<?php


namespace Test\Ecotone\Modelling\Unit\Config;

use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Config\EventBusRouter;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Conversion\AbstractSuperAdmin;
use Test\Ecotone\Messaging\Fixture\Conversion\Admin;
use Test\Ecotone\Messaging\Fixture\Conversion\Email;
use Test\Ecotone\Messaging\Fixture\Conversion\SuperAdmin;

/**
 * Class EventBusRouterTest
 * @package Test\Ecotone\Modelling\Unit\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventBusRouterTest extends TestCase
{
    public function test_routing_by_class()
    {
        $classNameToChannelNameMapping = [\stdClass::class => [\stdClass::class]];

        $this->assertEquals(
            [\stdClass::class],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByObject(new \stdClass())
        );
    }

    public function test_routing_by_object_type_hint()
    {
        $classNameToChannelNameMapping = [TypeDescriptor::OBJECT => [\stdClass::class]];

        $this->assertEquals(
            [\stdClass::class],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByObject(new \stdClass())
        );
    }

    public function test_routing_by_abstract_class()
    {
        $classNameToChannelNameMapping = [
            \stdClass::class => ["some"],
            AbstractSuperAdmin::class => ["abstractSuperAdmin"],
            SuperAdmin::class => ["superAdmin"]
        ];

        $this->assertEquals(
            ["abstractSuperAdmin", "superAdmin"],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByObject(new SuperAdmin())
        );
    }

    public function test_routing_by_interface()
    {
        $classNameToChannelNameMapping = [
            \stdClass::class => ["some"],
            Admin::class => ["admin"],
            SuperAdmin::class => ["superAdmin"],
            Email::class => ["email"]
        ];

        $this->assertEquals(
            ["admin", "email", "superAdmin"],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByObject(new SuperAdmin())
        );
    }

    public function test_routing_by_channel_name()
    {
        $classNameToChannelNameMapping = ["createOffer" => ["channel"]];

        $this->assertEquals(
            ["channel"],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByName("createOffer")
        );
    }

    public function test_routing_by_expression()
    {
        $classNameToChannelNameMapping = ["input.*" => ["someId"]];

        $this->assertEquals(
            ["someId"],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByName("input.test")
        );
    }

    public function test_merging_multiple_endpoints()
    {
        $classNameToChannelNameMapping = ["input.*" => ["someId1"], "*.test" => ["someId2"], "test" => ["someId3"], "input" => ["someId4"]];

        $this->assertEquals(
            ["someId1", "someId2"],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByName("input.test")
        );
    }

    /**
     * @param array $classNameToChannelNameMapping
     *
     * @return EventBusRouter
     */
    private function createEventRouter(array $classNameToChannelNameMapping): EventBusRouter
    {
        return new EventBusRouter($classNameToChannelNameMapping);
    }
}