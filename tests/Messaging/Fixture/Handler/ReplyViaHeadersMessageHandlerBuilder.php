<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;

/**
 * Class ReplyViaHeadersMessageHandlerBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReplyViaHeadersMessageHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    private function __construct(private mixed $replyData)
    {
    }

    /**
     * @param $replyData
     * @return ReplyViaHeadersMessageHandlerBuilder
     */
    public static function create($replyData): self
    {
        return new self($replyData);
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(ReplyViaHeadersMessageHandler::class, [$this->replyData], 'create');
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(ReplyViaHeadersMessageHandler::class, 'handle');
    }
}
