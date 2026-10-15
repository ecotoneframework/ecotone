<?php

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingWithDoubleEventHandlers;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;

use function end;

use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class OrderService
{
    private array $notificationHeaders = [];

    private array $notifyWithCustomHeaders = [];

    #[CommandHandler('placeOrder')]
    public function doSomething($command, array $headers, EventBus $eventBus): void
    {
        $eventBus->publish(new OrderWasPlaced());
    }

    #[CommandHandler('failAction')]
    public function failAction(): void
    {
        throw new InvalidArgumentException('failed action');
    }

    #[Asynchronous('orders')]
    #[EventHandler(endpointId: 'notifyOne')]
    public function notifyOne(OrderWasPlaced $event, array $headers, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('sendNotification', [], MediaType::APPLICATION_X_PHP_ARRAY, $this->notifyWithCustomHeaders);
    }

    #[Asynchronous('orders')]
    #[EventHandler(endpointId: 'notifyTwo')]
    public function notifyTwo(OrderWasPlaced $event, array $headers, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('sendNotification', [], MediaType::APPLICATION_X_PHP_ARRAY, $this->notifyWithCustomHeaders);
    }

    #[CommandHandler('setCustomNotificationHeaders')]
    public function notifyWithCustomerHeaders(array $payload, array $headers): void
    {
        $this->notifyWithCustomHeaders = $headers;
    }

    #[CommandHandler('sendNotification')]
    public function sendNotification($command, array $headers): void
    {
        $this->notificationHeaders[] = $headers;
    }

    #[Asynchronous('orders')]
    #[CommandHandler('sendNotificationViaCommandBus', endpointId: 'sendNotificationViaCommandBusEndpointId')]
    public function sendNotificationViaCommandBus(#[Reference] EventBus $eventBus): void
    {
        $eventBus->publish(new OrderWasPlaced());
    }

    #[QueryHandler('getNotificationHeaders')]
    public function getNotificationHeaders(): array
    {
        return end($this->notificationHeaders);
    }

    #[QueryHandler('getAllNotificationHeaders')]
    public function getAllNotificationHeaders(): array
    {
        return $this->notificationHeaders;
    }
}
