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
final class HeaderEnricher
{
    /**
     * HeaderEnricher constructor.
     * @param array $headers
     */
    public function __construct(private array $headers, private MessageBuilder $messageBuilder)
    {
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = $this->messageBuilder->fromMessage($message);

        return $messageToBuild
                ->setMultipleHeaders($this->headers)
                ->build();
    }
}
