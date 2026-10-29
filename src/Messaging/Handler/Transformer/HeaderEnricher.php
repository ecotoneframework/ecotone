<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;
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
final class HeaderEnricher implements DefinedObject
{
    private array $headers;

    /**
     * HeaderEnricher constructor.
     * @param array $headers
     */
    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public static function create(array $headers): self
    {
        return new self($headers);
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = MessageBuilder::fromMessage($message);

        return $messageToBuild
                ->setMultipleHeaders($this->headers)
                ->build();
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->headers,
        ], 'create');
    }
}
