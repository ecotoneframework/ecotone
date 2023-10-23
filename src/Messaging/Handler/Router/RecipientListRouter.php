<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;

/**
 * Class RecipientListRouter
 * @package Ecotone\Messaging\Handler\Router
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class RecipientListRouter implements DefinedObject
{
    /**
     * @var string[]
     */
    private array $recipientMessageChannels;

    /**
     * RecipientListRouter constructor.
     *
     * @param string[] $recipientMessageChannels
     */
    public function __construct(array $recipientMessageChannels)
    {
        $this->recipientMessageChannels = $recipientMessageChannels;
    }

    /**
     * @inheritDoc
     */
    public function route(Message $message): array
    {
        return $this->recipientMessageChannels;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->recipientMessageChannels]);
    }
}
