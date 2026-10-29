<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Modelling\Attribute\EventHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\NamedEvent\GuestWasAddedToBook;
use Test\Ecotone\Modelling\Fixture\RoutingTest\GuestWasAddedToBookConverter;
use Test\Ecotone\Modelling\Fixture\RoutingTest\RoutingTestHandler;

/**
 * @internal
 */
class RoutingTest extends TestCase
{
    public const ASYNC_CHANNEL = 'async';
    public static function handlerCases(): iterable
    {
        yield 'class' => new class () extends RoutingTestHandler {
            #[EventHandler(listenTo: GuestWasAddedToBook::EVENT_NAME, endpointId: 'async_class')]
            #[Asynchronous('async')]
            public function handleAsync(GuestWasAddedToBook $message): void
            {
                $this->messages[] = $message;
            }
        };

        yield 'object' => new class () extends RoutingTestHandler {
            #[EventHandler(listenTo: GuestWasAddedToBook::EVENT_NAME, endpointId: 'async_object')]
            #[Asynchronous('async')]
            public function handleAsyncCatchAll(object $message): void
            {
                $this->messages[] = $message;
            }
        };

        yield 'array' => new class () extends RoutingTestHandler {
            #[EventHandler(listenTo: GuestWasAddedToBook::EVENT_NAME, endpointId: 'async_array')]
            #[Asynchronous('async')]
            public function handleAsyncArray(array $message): void
            {
                $this->messages[] = $message;
            }
        };
    }

    public static function cases(): iterable
    {
        $asyncCases = ['synchronous' => false, 'asynchronous' => true];
        $eventTypeCases = [
            'array' => ['bookId' => 'an-id', 'guestName' => 'John Doe'],
            'object' => new GuestWasAddedToBook('an-id', 'John Doe'),
        ];

        foreach ($asyncCases as $async => $isAsync) {
            foreach ($eventTypeCases as $eventType => $event) {
                foreach (self::handlerCases() as $handlerType => $handler) {
                    $clonedHandler = clone $handler;
                    $clonedHandler->clearMessages();
                    yield "from $eventType to $handlerType ($async)" => [
                        'event' => $event,
                        'async' => $isAsync,
                        'handler' => $clonedHandler,
                    ];
                }
            }
        }
    }

    #[DataProvider('cases')]
    public function test_it_can_route_events_by_name_and_convert(mixed $event, bool $async, RoutingTestHandler $handler): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, GuestWasAddedToBook::class, GuestWasAddedToBookConverter::class],
            containerOrAvailableServices: [$handler, new GuestWasAddedToBookConverter()],
            enableAsynchronousProcessing:  $async ? [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ] : null
        );

        $ecotoneLite
            ->publishEventWithRoutingKey(GuestWasAddedToBook::EVENT_NAME, $event);
        if ($async) {
            $ecotoneLite->run('async');
        }
        $this->assertCount(1, $handler->getMessages());
    }
}
