<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter;

use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter\MessageDrivenChannelAdapter;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class MessageDrivenConsumer
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageDrivenConsumer implements ConsumerLifecycle
{
    /**
     * @var MessageDrivenChannelAdapter
     */
    private $messageDrivenChannelAdapter;
    /**
     * @var string
     */
    private $endpointName;

    /**
     * MessageDrivenConsumer constructor.
     * @param string $endpointName
     * @param MessageDrivenChannelAdapter $messageDrivenChannelAdapter
     */
    private function __construct(string $endpointName, MessageDrivenChannelAdapter $messageDrivenChannelAdapter)
    {
        $this->endpointName = $endpointName;
        $this->messageDrivenChannelAdapter = $messageDrivenChannelAdapter;
    }

    /**
     * @param string $endpointName
     * @param MessageDrivenChannelAdapter $messageDrivenChannelAdapter
     * @return MessageDrivenConsumer
     */
    public static function create(string $endpointName, MessageDrivenChannelAdapter $messageDrivenChannelAdapter) : self
    {
        return new self($endpointName, $messageDrivenChannelAdapter);
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->messageDrivenChannelAdapter->startMessageDrivenConsumer();
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->endpointName;
    }
}