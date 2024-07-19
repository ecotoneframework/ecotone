<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use InvalidArgumentException;

/**
 * Class ExceptionMessageHandler
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ExceptionMessageHandler implements MessageHandler, DefinedObject
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        throw new InvalidArgumentException('testing exception');
    }

    public function __toString()
    {
        return self::class;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [], 'create');
    }
}
