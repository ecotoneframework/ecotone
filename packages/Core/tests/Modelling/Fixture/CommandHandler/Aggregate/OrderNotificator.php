<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class OrderNotificator
{
    /** @var Notification[] */
    private $notifications = [];

    #[EventHandler]
    public function notify(Notification $notification) : void
    {
        $this->notifications[] = $notification;
    }

    #[QueryHandler("getOrderNotifications")]
    public function getNotifications(array $query) : array
    {
        return $this->notifications;
    }
}