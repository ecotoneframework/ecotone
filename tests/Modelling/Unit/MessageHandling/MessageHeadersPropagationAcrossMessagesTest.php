<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit\MessageHandling;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\AggregateNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageHeadersPropagationAcrossMessagesTest extends TestCase
{
    public function test_headers_of_a_handled_message_do_not_reach_an_event_published_after_it(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommandWithRouting('order.publish', metadata: ['tenant' => 'first']);
        $ecotone->publishEventWithRouting('order.published.outside');

        $eventHeaders = $ecotone->popRecordedEventHeaders();
        self::assertSame('first', $eventHeaders[0]->get('tenant'));
        self::assertFalse($eventHeaders[1]->containsKey('tenant'));
    }

    public function test_headers_of_a_failed_message_do_not_reach_the_next_message(): void
    {
        $ecotone = $this->bootstrap();

        try {
            $ecotone->sendCommandWithRouting('order.fail', metadata: ['tenant' => 'first']);
            self::fail('Expected the first command to fail');
        } catch (RuntimeException) {
        }
        $ecotone->sendCommandWithRouting('order.publish');

        self::assertFalse($ecotone->popRecordedEventHeaders()[0]->containsKey('tenant'));
    }

    public function test_the_causation_chain_of_a_failed_message_does_not_reach_the_next_message(): void
    {
        $ecotone = $this->bootstrap();

        try {
            $ecotone->sendCommandWithRouting('order.closeMissingTicket');
            self::fail('Expected the nested command to miss its aggregate');
        } catch (AggregateNotFoundException $exception) {
            self::assertStringContainsString('while handling command order.closeMissingTicket', $exception->getMessage());
        }

        try {
            $ecotone->sendCommandWithRouting('ticket.close', metadata: ['aggregate.id' => 'missing']);
            self::fail('Expected the command to miss its aggregate');
        } catch (AggregateNotFoundException $exception) {
            self::assertStringNotContainsString('order.closeMissingTicket', $exception->getMessage());
            self::assertStringNotContainsString('while handling', $exception->getMessage());
        }
    }

    private function bootstrap(): FlowTestSupport
    {
        $orderService = new class () {
            #[CommandHandler('order.publish')]
            public function publish(array $command, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('order.published');
            }

            #[CommandHandler('order.fail')]
            public function fail(): void
            {
                throw new RuntimeException('Order failed');
            }

            #[CommandHandler('order.closeMissingTicket')]
            public function closeMissingTicket(array $command, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('ticket.close', metadata: ['aggregate.id' => 'missing']);
            }
        };
        $ticket = new #[Aggregate] class () {
            #[Identifier]
            public string $ticketId = '';

            #[CommandHandler('ticket.close')]
            public function close(): void
            {
            }
        };

        return EcotoneLite::bootstrapFlowTesting([$orderService::class, $ticket::class], [$orderService], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }
}
