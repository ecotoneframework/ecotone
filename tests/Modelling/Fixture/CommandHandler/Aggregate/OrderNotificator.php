<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

class OrderNotificator
{
    /** @var Notification[] */
    private $notifications = [];

    /**
     * @param Notification $notification
     * @EventHandler()
     */
    public function notify(Notification $notification) : void
    {
        $this->notifications[] = $notification;
    }

    /**
     * @param array $query
     * @return array
     * @QueryHandler(inputChannelName="getOrderNotifications")
     */
    public function getNotifications(array $query) : array
    {
        return $this->notifications;
    }
}