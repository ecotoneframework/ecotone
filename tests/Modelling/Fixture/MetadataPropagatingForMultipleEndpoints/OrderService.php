<?php


namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints;


use Ecotone\Messaging\Annotation\Asynchronous;
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
    public function notifyBySms(OrderWasPlaced $event, array $headers, EventBus $eventBus) : void
    {
        $this->notificationHeaders[] = $headers;
    }

    /**
     * @EventHandler(endpointId="notificationEndpoint")
     */
    #[Asynchronous("notifications")]
    public function notifyByEmail(OrderWasPlaced $event, array $headers, EventBus $eventBus) : void
    {
        $this->notificationHeaders[] = $headers;
    }

    /**
     * @CommandHandler("setCustomNotificationHeaders")
     */
    public function notifyWithCustomerHeaders(array $payload, array $headers) : void
    {
        $this->notifyWithCustomHeaders = $headers;
    }

    /**
     * @QueryHandler("getNotificationHeaders")
     */
    public function getNotificationHeaders() : array
    {
        return array_shift($this->notificationHeaders);
    }
}