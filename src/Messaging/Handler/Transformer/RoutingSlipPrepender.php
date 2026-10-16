<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class HeaderEnricher
 * @package Ecotone\Messaging\Handler\Transformer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
final class RoutingSlipPrepender
{
    /**
     * HeaderEnricher constructor.
     * @param string[] $routingSlip
     */
    public function __construct(private array $routingSlip, private array $unsetHeaders, private MessageBuilder $messageBuilder)
    {
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = $this->messageBuilder->fromMessage($message);

        return $messageToBuild
                ->prependRoutingSlip($this->routingSlip)
                ->removeHeaders($this->unsetHeaders)
                ->build();
    }
}
