<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Router;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Priority;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class BusRoutingTest extends TestCase
{
    public function test_multiple_handlers_for_the_same_event_class_fire_in_priority_order(): void
    {
        $handler = new RoutingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([RoutingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->publishEvent(new AEvent());

        $this->assertLessThan(
            array_search('lowPriority', $handler->calls, true),
            array_search('highPriority', $handler->calls, true),
        );
    }

    public function test_handler_typed_to_a_parent_class_also_fires_for_a_subclass_event(): void
    {
        $handler = new RoutingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([RoutingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->publishEvent(new BEvent());

        $this->assertContains('highPriority', $handler->calls);
        $this->assertContains('lowPriority', $handler->calls);
    }

    public function test_handler_typed_to_an_implemented_interface_fires_for_the_event(): void
    {
        $handler = new RoutingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([RoutingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->publishEvent(new AEvent());

        $this->assertContains('interfaceHandler', $handler->calls);
    }

    public function test_named_routing_key_exact_match_and_wildcard_both_fire(): void
    {
        $handler = new RoutingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([RoutingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->publishEventWithRouting('test.named', 'payload');

        $this->assertContains('namedHandler', $handler->calls);
        $this->assertContains('wildcardHandler', $handler->calls);
    }

    public function test_named_routing_key_wildcard_matches_an_unknown_but_prefixed_key(): void
    {
        $handler = new RoutingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([RoutingHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->publishEventWithRouting('test.unknown', 'payload');

        $this->assertSame(['wildcardHandler'], $handler->calls);
    }

    public function test_a_handler_typed_to_the_native_object_type_catches_every_event_class(): void
    {
        $handler = new CatchAllHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([CatchAllHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->publishEvent(new AEvent());
        $ecotone->publishEvent(new UnrelatedEvent());

        $this->assertSame([AEvent::class, UnrelatedEvent::class], $handler->receivedClasses);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface NotificationInterface
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
class AEvent implements NotificationInterface
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
class BEvent extends AEvent
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
class UnrelatedEvent
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CatchAllHandler
{
    public array $receivedClasses = [];

    #[EventHandler]
    public function onAnyEvent(object $event): void
    {
        $this->receivedClasses[] = $event::class;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RoutingHandler
{
    public array $calls = [];

    #[Priority(3)]
    #[EventHandler(endpointId: 'highPriority')]
    public function highPriority(AEvent $event): void
    {
        $this->calls[] = 'highPriority';
    }

    #[Priority(2)]
    #[EventHandler(endpointId: 'lowPriority')]
    public function lowPriority(AEvent $event): void
    {
        $this->calls[] = 'lowPriority';
    }

    #[EventHandler(endpointId: 'interfaceHandler')]
    public function onNotification(NotificationInterface $event): void
    {
        $this->calls[] = 'interfaceHandler';
    }

    #[EventHandler(listenTo: 'test.named', endpointId: 'namedHandler')]
    public function named(string $payload): void
    {
        $this->calls[] = 'namedHandler';
    }

    #[EventHandler(listenTo: 'test.*', endpointId: 'wildcardHandler')]
    public function wildcard(string $payload): void
    {
        $this->calls[] = 'wildcardHandler';
    }
}
