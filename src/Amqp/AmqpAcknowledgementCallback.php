<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Queue\Consumer as EnqueueConsumer;
use Interop\Queue\Message as EnqueueMessage;
use SimplyCodedSoftware\Messaging\Endpoint\AcknowledgementCallback;

/**
 * Class EnqueueAcknowledgementCallback
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpAcknowledgementCallback implements AcknowledgementCallback
{
    const AUTO_ACK = "auto";
    const MANUAL_ACK = "manual";
    const NONE = "none";

    /**
     * @var bool
     */
    private $isAutoAck;
    /**
     * @var EnqueueConsumer
     */
    private $enqueueConsumer;
    /**
     * @var EnqueueMessage
     */
    private $enqueueMessage;

    /**
     * EnqueueAcknowledgementCallback constructor.
     * @param bool $isAutoAck
     * @param EnqueueConsumer $enqueueConsumer
     * @param EnqueueMessage $enqueueMessage
     */
    private function __construct(bool $isAutoAck, EnqueueConsumer $enqueueConsumer, EnqueueMessage $enqueueMessage)
    {
        $this->isAutoAck = $isAutoAck;
        $this->enqueueConsumer = $enqueueConsumer;
        $this->enqueueMessage = $enqueueMessage;
    }

    /**
     * @param EnqueueConsumer $enqueueConsumer
     * @param EnqueueMessage $enqueueMessage
     * @return AmqpAcknowledgementCallback
     */
    public static function createWithAutoAck(EnqueueConsumer $enqueueConsumer, EnqueueMessage $enqueueMessage) : self
    {
        return new self(true, $enqueueConsumer, $enqueueMessage);
    }

    /**
     * @param EnqueueConsumer $enqueueConsumer
     * @param EnqueueMessage $enqueueMessage
     * @return AmqpAcknowledgementCallback
     */
    public static function createWithManualAck(EnqueueConsumer $enqueueConsumer, EnqueueMessage $enqueueMessage) : self
    {
        return new self(false, $enqueueConsumer, $enqueueMessage);
    }

    /**
     * @inheritDoc
     */
    public function isAutoAck(): bool
    {
        return $this->isAutoAck;
    }

    /**
     * @inheritDoc
     */
    public function disableAutoAck(): void
    {
        $this->isAutoAck = false;
    }

    /**
     * @inheritDoc
     */
    public function accept(): void
    {
        $this->enqueueConsumer->acknowledge($this->enqueueMessage);
    }

    /**
     * @inheritDoc
     */
    public function reject(): void
    {
        $this->enqueueConsumer->reject($this->enqueueMessage);
    }

    /**
     * @inheritDoc
     */
    public function requeue(): void
    {
        $this->enqueueConsumer->reject($this->enqueueMessage, true);
    }
}