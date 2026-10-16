<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class HeaderMapperTransformer
{
    public function __construct(private array $mappedHeaders, private MessageBuilder $messageBuilder)
    {
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = $this->messageBuilder->fromMessage($message);

        foreach ($this->mappedHeaders as $fromHeaderName => $toHeaderName) {
            if ($message->getHeaders()->containsKey($fromHeaderName)) {
                $messageToBuild = $messageToBuild
                    ->setHeader($toHeaderName, $message->getHeaders()->get($fromHeaderName));
            }
        }

        return $messageToBuild->build();
    }
}
