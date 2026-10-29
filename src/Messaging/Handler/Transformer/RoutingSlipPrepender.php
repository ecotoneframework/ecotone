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
final class RoutingSlipPrepender implements DefinedObject
{
    /**
     * HeaderEnricher constructor.
     * @param string[] $routingSlip
     */
    private function __construct(private array $routingSlip, private array $unsetHeaders)
    {
    }

    public static function create(array $routingSlip, array $unsetHeaders): self
    {
        return new self($routingSlip, $unsetHeaders);
    }

    public function transform(Message $message): Message
    {
        $messageToBuild = MessageBuilder::fromMessage($message);

        return $messageToBuild
                ->prependRoutingSlip($this->routingSlip)
                ->removeHeaders($this->unsetHeaders)
                ->build();
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->routingSlip,
            $this->unsetHeaders,
        ], 'create');
    }
}
