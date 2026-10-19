<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\BusinessMethod;
use Ecotone\Api\Attribute\ConsoleCommand;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessagingSystemContainerLookupTest extends TestCase
{
    public function test_fetching_an_unregistered_gateway_names_the_registered_ones_and_the_attributes_that_register_one(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: []);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Gateway `App\Billing\InvoiceGateway` is not registered. Registered gateways: ');
        $this->expectExceptionMessage('`' . CommandBus::class . '`');
        $this->expectExceptionMessage(sprintf('Register one by marking an interface method with #[%s] or #[%s], or fetch a registered gateway by its reference name.', MessageGateway::class, BusinessMethod::class));

        $ecotone->getGateway('App\Billing\InvoiceGateway');
    }

    public function test_running_an_unregistered_console_command_names_the_registered_ones_and_the_attribute_that_registers_one(): void
    {
        $console = new class () {
            #[ConsoleCommand('billing:close-month')]
            public function closeMonth(): void
            {
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$console::class], [$console], enabledModules: []);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Console command `billing:close-year` is not registered. Registered console commands: `ecotone:run`, `ecotone:list`, ');
        $this->expectExceptionMessage('`billing:close-month`');
        $this->expectExceptionMessage(sprintf('Register one by marking a method with #[%s(\'billing:close-year\')], or run a registered command by its name.', ConsoleCommand::class));

        $ecotone->runConsoleCommand('billing:close-year', []);
    }
}
