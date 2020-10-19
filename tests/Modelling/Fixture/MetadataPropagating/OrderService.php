<?php


namespace Test\Ecotone\Modelling\Fixture\MetadataPropagating;


use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use PHPUnit\Framework\Assert;

class OrderService
{
    private array $notificationHeaders = [];

    private array $notifyWithCustomHeaders = [];

    /**
     * @CommandHandler()
     */
    public function doSomething(PlaceOrder $command, array $headers, EventBus $eventBus) : void
    {
        $eventBus->send(new OrderWasPlaced());
    }

    /**
     * @CommandHandler("failAction")
     */
    public function failAction() : void
    {
        throw new \InvalidArgumentException("failed action");
    }

    /**
     * @EventHandler()
     */
    public function notify(OrderWasPlaced $event, array $headers, CommandBus $commandBus) : void
    {
        $commandBus->sendWithMetadata(new SendNotification(), $this->notifyWithCustomHeaders);
    }

    /**
     * @CommandHandler("setCustomNotificationHeaders")
     */
    public function notifyWithCustomerHeaders(array $payload, array $headers) : void
    {
        $this->notifyWithCustomHeaders = $headers;
    }

    /**
     * @CommandHandler()
     */
    public function sendNotification(SendNotification $command, array $headers) : void
    {
        $this->notificationHeaders = $headers;
    }

    /**
     * @QueryHandler("getNotificationHeaders")
     */
    public function getNotificationHeaders() : array
    {
        return $this->notificationHeaders;
    }
}