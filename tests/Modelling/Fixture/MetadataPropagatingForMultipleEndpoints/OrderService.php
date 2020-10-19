<?php


namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints;


use Ecotone\Messaging\Conversion\MediaType;
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
     * @CommandHandler("placeOrder")
     */
    public function doSomething($command, array $headers, EventBus $eventBus) : void
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
    public function notifyBySms(OrderWasPlaced $event, EventBus $eventBus) : void
    {
        $eventBus->send(new NotificationWasPrepared());
    }

    /**
     * @EventHandler()
     */
    public function notifyByEmail(OrderWasPlaced $event, EventBus $eventBus) : void
    {
        $eventBus->send(new NotificationWasPrepared());
    }

    /**
     * @CommandHandler("setCustomNotificationHeaders")
     */
    public function notifyWithCustomerHeaders(array $payload, array $headers) : void
    {
        $this->notifyWithCustomHeaders = $headers;
    }

    /**
     * @EventHandler("sendNotification")
     */
    public function sendNotification(NotificationWasPrepared $event, array $headers) : void
    {
        $this->notificationHeaders[] = $headers;
    }

    /**
     * @QueryHandler("getNotificationHeaders")
     */
    public function getNotificationHeaders() : array
    {
        return array_shift($this->notificationHeaders);
    }
}