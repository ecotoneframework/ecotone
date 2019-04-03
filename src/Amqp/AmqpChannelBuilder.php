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
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        // TODO: Implement getMessageChannelName() method.
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