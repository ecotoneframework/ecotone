<?php


namespace Test\Ecotone\Modelling\Fixture\MetadataPropagating;


use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use PHPUnit\Framework\Assert;

class OrderService
{
    private array $notificationHeaders = [];

    private array $notifyWithCustomHeaders = [];

    #[CommandHandler("placeOrder")]
    public function doSomething($command, array $headers, EventBus $eventBus) : void
    {
        $eventBus->publish(new OrderWasPlaced());
    }

    #[CommandHandler("failAction")]
    public function failAction() : void
    {
        throw new \InvalidArgumentException("failed action");
    }

    #[EventHandler]
    public function notify(OrderWasPlaced $event, array $headers, CommandBus $commandBus) : void
    {
        $commandBus->sendWithRouting("sendNotification", [], MediaType::APPLICATION_X_PHP_ARRAY, $this->notifyWithCustomHeaders);
    }

    #[CommandHandler("setCustomNotificationHeaders")]
    public function notifyWithCustomerHeaders(array $payload, array $headers) : void
    {
        $this->notifyWithCustomHeaders = $headers;
    }

    #[CommandHandler("sendNotification")]
    public function sendNotification($command, array $headers) : void
    {
        $this->notificationHeaders = $headers;
    }

    #[QueryHandler("getNotificationHeaders")]
    public function getNotificationHeaders() : array
    {
        return $this->notificationHeaders;
    }
}