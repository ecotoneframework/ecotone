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
     * @param string[] $unsetHeaders
     */
    public function __construct(private array $routingSlip, private array $unsetHeaders, private MessageBuilder $messageBuilder, private string $routingKeyHeader)
    {
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = $this->messageBuilder->fromMessage($message);

        foreach ($this->unsetHeaders as $busRoutingHeader) {
            if ($message->getHeaders()->containsKey($busRoutingHeader)) {
                $messageToBuild = $messageToBuild->setHeader($this->routingKeyHeader, $message->getHeaders()->get($busRoutingHeader));
                break;
            }
        }

        return $messageToBuild
                ->prependRoutingSlip($this->routingSlip)
                ->removeHeaders($this->unsetHeaders)
                ->build();
    }
}
