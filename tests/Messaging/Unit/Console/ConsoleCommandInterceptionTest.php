<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Console;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\ConsoleCommand;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class ConsoleCommandInterceptionTest extends TestCase
{
    public function test_running_and_listing_consumers_is_not_wrapped_as_a_console_command(): void
    {
        $handler = new class () {
            public array $consoleCommands = [];
            public array $handled = [];

            #[Around(pointcut: ConsoleCommand::class)]
            public function record(MethodInvocation $invocation, ConsoleCommand $consoleCommand): mixed
            {
                $this->consoleCommands[] = $consoleCommand->getName();

                return $invocation->proceed();
            }

            #[Asynchronous('async')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $order): void
            {
                $this->handled[] = $order;
            }

            #[ConsoleCommand('app:orders')]
            public function orders(): void
            {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
        );
        $ecotone->sendCommandWithRouting('order.place', 'coffee');

        $ecotone->runConsoleCommand('ecotone:run', ['consumerName' => 'async', 'finishWhenNoMessages' => true]);
        $ecotone->runConsoleCommand('ecotone:list');
        $ecotone->runConsoleCommand('app:orders');

        self::assertSame(['coffee'], $handler->handled);
        self::assertSame(['app:orders'], $handler->consoleCommands);
    }
}
