<?php


namespace Test\SimplyCodedSoftware\DomainModel\Unit\Config;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\DomainModel\Config\EventBusRouter;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\AbstractSuperAdmin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Admin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Email;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\SuperAdmin;

/**
 * Class EventBusRouterTest
 * @package Test\SimplyCodedSoftware\DomainModel\Unit\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventBusRouterTest extends TestCase
{
    public function test_routing_by_object()
    {
        $classNameToChannelNameMapping = [\stdClass::class => [\stdClass::class]];

        $this->assertEquals(
            [\stdClass::class],
            $this->createEventRouter($classNameToChannelNameMapping)->routeByObject(new \stdClass())
        );
    }

    public function test_routing_by_channel_name()
    {
        $classNameToChannelNameMapping = [\stdClass::class => ["some"]];

        $this->assertEquals(
            "some",
            $this->createEventRouter($classNameToChannelNameMapping)->routeByName("some")
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

    /**
     * @param array $classNameToChannelNameMapping
     *
     * @return EventBusRouter
     */
    private function createEventRouter(array $classNameToChannelNameMapping): EventBusRouter
    {
        $eventBusRouter = new EventBusRouter($classNameToChannelNameMapping);

        return $eventBusRouter;
}
}