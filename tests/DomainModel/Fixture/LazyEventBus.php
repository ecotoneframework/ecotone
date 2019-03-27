<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture;

use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;

/**
 * Class LazyEventBus
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBus implements EventBus
{
    /**
     * @var EventBus
     */
    private $eventBus;


    /**
     * @inheritDoc
     */
    public function send($event)
    {
        if ($this->eventBus) {
            $this->eventBus->send($event);
        }
    }

    /**
     * @inheritDoc
     */
    public function convertAndSend(string $name, MediaType $dataMediaType, $commandData)
    {
        if ($this->eventBus) {
            $this->eventBus->convertAndSend($name, $dataMediaType, $commandData);
        }
    }

    /**
     * @inheritDoc
     */
    public function convertAndSendWithMetadata(string $name, MediaType $dataMediaType, $commandData, array $metadata)
    {
        if ($this->eventBus) {
            $this->eventBus->convertAndSendWithMetadata($name, $dataMediaType, $commandData, $metadata);
        }
    }

    /**
     * @inheritDoc
     */
    public function sendWithMetadata($event, array $metadata)
    {
        if ($this->eventBus) {
            $this->eventBus->sendWithMetadata($event, $metadata);
        }
    }

    public function setEventBus(EventBus $eventBus) : void
    {
        $this->eventBus = $eventBus;
    }

}