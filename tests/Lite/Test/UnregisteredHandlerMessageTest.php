<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Lite\Fixtures\UnregisteredHandler\Command\ReserveShippingSlot;

/**
 * licence Apache-2.0
 * @internal
 */
final class UnregisteredHandlerMessageTest extends TestCase
{
    public function test_command_handled_by_a_class_missing_from_the_bootstrap_names_the_class_to_register(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            enabledModules: [],
        );

        $this->expectException(DestinationResolutionException::class);
        $this->expectExceptionMessage("Can't send command to Test\\Ecotone\\Lite\\Fixtures\\UnregisteredHandler\\Command\\ReserveShippingSlot. It is handled by Test\\Ecotone\\Lite\\Fixtures\\UnregisteredHandler\\ShippingSlotReservationHandler::reserve(), which is not registered in this Ecotone Lite bootstrap. Add Test\\Ecotone\\Lite\\Fixtures\\UnregisteredHandler\\ShippingSlotReservationHandler to the classesToResolve of EcotoneLite::bootstrapFlowTesting(), or load its namespace with EcotoneLite::bootstrapFlowTesting(..., namespaces: ['Test\\Ecotone\\Lite\\Fixtures\\UnregisteredHandler']).");

        $ecotone->sendCommand(new ReserveShippingSlot('order-1'));
    }

    public function test_command_routing_key_handled_by_a_class_missing_from_the_bootstrap_names_the_class_to_register(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            enabledModules: [],
        );

        $this->expectException(DestinationResolutionException::class);
        $this->expectExceptionMessage("Can't send command to shipping.cancelSlot. It is handled by Test\\Ecotone\\Lite\\Fixtures\\UnregisteredHandler\\ShippingSlotReservationHandler::cancel(), which is not registered in this Ecotone Lite bootstrap.");

        $ecotone->sendCommandWithRouting('shipping.cancelSlot', 'order-1');
    }

    public function test_query_handled_by_a_class_missing_from_the_bootstrap_names_the_class_to_register(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            enabledModules: [],
        );

        $this->expectException(DestinationResolutionException::class);
        $this->expectExceptionMessage("Can't send query to shipping.getSlot. It is handled by Test\\Ecotone\\Lite\\Fixtures\\UnregisteredHandler\\ShippingSlotReservationHandler::getSlot(), which is not registered in this Ecotone Lite bootstrap.");

        $ecotone->sendQueryWithRouting('shipping.getSlot', 'order-1');
    }

    public function test_command_without_any_handler_says_what_to_add(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            enabledModules: [],
        );

        $this->expectException(DestinationResolutionException::class);
        $this->expectExceptionMessage("Can't send command to order.archive. No Command Handler is registered for it in this Ecotone Lite bootstrap. Add #[CommandHandler('order.archive')] to a method and add its class to the classesToResolve of EcotoneLite::bootstrapFlowTesting().");

        $ecotone->sendCommandWithRouting('order.archive', 'order-1');
    }
}
