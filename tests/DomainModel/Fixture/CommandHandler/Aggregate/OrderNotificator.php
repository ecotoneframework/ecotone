<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\DomainModel\Annotation\EventHandler;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class OrderNotificator
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
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