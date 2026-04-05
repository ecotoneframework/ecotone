<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Attribute;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\AsynchronousEndpointAttribute;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\WithoutMessageCollector;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\EventBus;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[Attribute]
class CustomAsyncAttribute implements AsynchronousEndpointAttribute
{
    public function __construct(public string $value)
    {
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class AsyncEndpointAnnotationTest extends TestCase
{
    public function test_around_interceptor_receives_handler_attribute_on_async_endpoint(): void
    {
        $collector = new stdClass();
        $collector->receivedAttribute = null;

        $handler = new class () {
            #[Asynchronous('async', endpointAnnotations: [new CustomAsyncAttribute('test-value')])]
            #[CommandHandler('doWork', endpointId: 'doWork.endpoint')]
            public function handle(string $payload): void
            {
            }
        };

        $interceptor = new class ($collector) {
            public function __construct(private stdClass $collector)
            {
            }

            #[Around(pointcut: AsynchronousRunningEndpoint::class)]
            public function intercept(MethodInvocation $methodInvocation, ?CustomAsyncAttribute $attr = null): mixed
            {
                $this->collector->receivedAttribute = $attr;

                return $methodInvocation->proceed();
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class, $interceptor::class],
            [$handler, $interceptor],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('doWork', 'test');
        $ecotoneLite->run('async');

        $this->assertNotNull($collector->receivedAttribute);
        $this->assertInstanceOf(CustomAsyncAttribute::class, $collector->receivedAttribute);
        $this->assertSame('test-value', $collector->receivedAttribute->value);
    }

    public function test_before_interceptor_receives_handler_attribute_on_async_endpoint(): void
    {
        $collector = new stdClass();
        $collector->receivedAttribute = null;

        $handler = new class () {
            #[Asynchronous('async', endpointAnnotations: [new CustomAsyncAttribute('before-value')])]
            #[CommandHandler('doWork', endpointId: 'doWork.endpoint')]
            public function handle(string $payload): void
            {
            }
        };

        $interceptor = new class ($collector) {
            public function __construct(private stdClass $collector)
            {
            }

            #[Before(pointcut: AsynchronousRunningEndpoint::class)]
            public function intercept(string $payload, ?CustomAsyncAttribute $attr = null): string
            {
                $this->collector->receivedAttribute = $attr;

                return $payload;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class, $interceptor::class],
            [$handler, $interceptor],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('doWork', 'test');
        $ecotoneLite->run('async');

        $this->assertNotNull($collector->receivedAttribute);
        $this->assertInstanceOf(CustomAsyncAttribute::class, $collector->receivedAttribute);
        $this->assertSame('before-value', $collector->receivedAttribute->value);
    }

    public function test_multiple_handlers_on_same_channel_resolve_correct_attribute(): void
    {
        $collector = new stdClass();
        $collector->receivedAttributes = [];

        $handler = new class () {
            #[Asynchronous('async', endpointAnnotations: [new CustomAsyncAttribute('handler-one')])]
            #[CommandHandler('doWorkOne', endpointId: 'doWorkOne.endpoint')]
            public function handleOne(string $payload): void
            {
            }

            #[Asynchronous('async', endpointAnnotations: [new CustomAsyncAttribute('handler-two')])]
            #[CommandHandler('doWorkTwo', endpointId: 'doWorkTwo.endpoint')]
            public function handleTwo(string $payload): void
            {
            }
        };

        $interceptor = new class ($collector) {
            public function __construct(private stdClass $collector)
            {
            }

            #[Around(pointcut: AsynchronousRunningEndpoint::class)]
            public function intercept(MethodInvocation $methodInvocation, ?CustomAsyncAttribute $attr = null): mixed
            {
                $this->collector->receivedAttributes[] = $attr;

                return $methodInvocation->proceed();
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class, $interceptor::class],
            [$handler, $interceptor],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('doWorkOne', 'test');
        $ecotoneLite->run('async');

        $ecotoneLite->sendCommandWithRoutingKey('doWorkTwo', 'test');
        $ecotoneLite->run('async');

        $this->assertCount(2, $collector->receivedAttributes);
        $this->assertSame('handler-one', $collector->receivedAttributes[0]->value);
        $this->assertSame('handler-two', $collector->receivedAttributes[1]->value);
    }

    public function test_handler_without_custom_attribute_returns_null(): void
    {
        $collector = new stdClass();
        $collector->receivedAttribute = 'not-set';

        $handler = new class () {
            #[Asynchronous('async')]
            #[CommandHandler('doWork', endpointId: 'doWork.endpoint')]
            public function handle(string $payload): void
            {
            }
        };

        $interceptor = new class ($collector) {
            public function __construct(private stdClass $collector)
            {
            }

            #[Around(pointcut: AsynchronousRunningEndpoint::class)]
            public function intercept(MethodInvocation $methodInvocation, ?CustomAsyncAttribute $attr = null): mixed
            {
                $this->collector->receivedAttribute = $attr;

                return $methodInvocation->proceed();
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class, $interceptor::class],
            [$handler, $interceptor],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendCommandWithRoutingKey('doWork', 'test');
        $ecotoneLite->run('async');

        $this->assertNull($collector->receivedAttribute);
    }

    public function test_endpoint_annotations_require_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        $handler = new class () {
            #[Asynchronous('async', endpointAnnotations: [new CustomAsyncAttribute('test')])]
            #[CommandHandler('doWork', endpointId: 'doWork.endpoint')]
            public function handle(string $payload): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
        );
    }

    public function test_collector_holds_events_published_during_async_handler_and_discards_on_failure(): void
    {
        $handler = new class () {
            #[Asynchronous('async')]
            #[CommandHandler('doWork', endpointId: 'doWork.endpoint')]
            public function handle(string $payload, EventBus $eventBus): void
            {
                $eventBus->publish(new stdClass());
                throw new RuntimeException('Handler failure after publishing event');
            }
        };

        $eventHandler = new class () {
            #[Asynchronous('events')]
            #[EventHandler(endpointId: 'event.endpoint')]
            public function handle(stdClass $event): void
            {
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class, $eventHandler::class],
            [$handler, $eventHandler],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollableChannelConfiguration::neverRetry('events')->withCollector(true),
                ]),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('events'),
            ],
        );

        $ecotoneLite->sendCommandWithRoutingKey('doWork', 'test');

        try {
            $ecotoneLite->run('async');
        } catch (RuntimeException) {
        }

        $message = $ecotoneLite->getMessageChannel('events')->receive();
        self::assertNull($message, 'Collector should discard events when handler fails');
    }

    public function test_without_message_collector_events_are_sent_directly_and_survive_handler_failure(): void
    {
        $handler = new class () {
            #[Asynchronous('async', endpointAnnotations: [new WithoutMessageCollector()])]
            #[CommandHandler('doWork', endpointId: 'doWork.endpoint')]
            public function handle(string $payload, EventBus $eventBus): void
            {
                $eventBus->publish(new stdClass());
                throw new RuntimeException('Handler failure after publishing event');
            }
        };

        $eventHandler = new class () {
            #[Asynchronous('events')]
            #[EventHandler(endpointId: 'event.endpoint')]
            public function handle(stdClass $event): void
            {
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class, $eventHandler::class],
            [$handler, $eventHandler],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollableChannelConfiguration::neverRetry('events')->withCollector(true),
                ]),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('events'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('doWork', 'test');

        try {
            $ecotoneLite->run('async');
        } catch (RuntimeException) {
        }

        $message = $ecotoneLite->getMessageChannel('events')->receive();
        self::assertNotNull($message, 'Without collector, events should be sent directly and survive handler failure');
    }
}
