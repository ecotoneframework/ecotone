<?php

namespace Messaging\Handler\Transformer;

use Messaging\Message;
use Messaging\Support\MessageBuilder;

/**
 * Class HeaderEnricher
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class HeaderEnricher
{
    /**
     * @var array
     */
    private $headers;

    /**
     * HeaderEnricher constructor.
     * @param array $headers
     */
    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public static function create(array $headers) : self
    {
        return new self($headers);
    }

    public function transform(Message $message) : Message
    {
        $messageToBuild = MessageBuilder::fromMessage($message);

        return $messageToBuild
                ->setMultipleHeaders($this->headers)
                ->build();
    }
}