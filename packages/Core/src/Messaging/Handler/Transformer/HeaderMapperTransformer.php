<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

final class HeaderMapperTransformer
{
    private function __construct(private array $mappedHeaders)
    {
    }

    public static function create(array $mappedHeaders): self
    {
        return new self($mappedHeaders);
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = MessageBuilder::fromMessage($message);

        foreach ($this->mappedHeaders as $fromHeaderName => $toHeaderName) {
            if ($message->getHeaders()->containsKey($fromHeaderName)) {
                $messageToBuild = $messageToBuild
                    ->setHeader($toHeaderName, $message->getHeaders()->get($fromHeaderName));
            }
        }

        return $messageToBuild->build();
    }
}
