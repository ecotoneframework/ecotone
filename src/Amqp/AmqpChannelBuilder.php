<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Class AmqpChannelBuilder
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var string
     */
    private $amqpConnectionReferenceName;

    /**
     * AmqpChannelBuilder constructor.
     * @param string $channelName
     * @param string $amqpConnectionReferenceName
     */
    private function __construct(string $channelName, string $amqpConnectionReferenceName)
    {
        $this->channelName = $channelName;
        $this->amqpConnectionReferenceName = $amqpConnectionReferenceName;
    }


    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        // TODO: Implement build() method.
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        // TODO: Implement getRequiredReferenceNames() method.
    }
}