<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Config;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Presend;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;

/**
 * licence Apache-2.0
 */
final class MessageHandlerLogger
{
    #[Presend(precedence: 1000, pointcut: Asynchronous::class)]
    public function asynchronous(Message $message, #[Reference] LoggingGateway $loggingGateway, Asynchronous $asynchronous, ?IdentifiedAnnotation $identifiedAnnotation = null): void
    {
        $loggingGateway->info(
            sprintf('Message Handler%s is Asynchronous, sending Message to `%s` Message Channel', $identifiedAnnotation ? ' with endpoint id ' . $identifiedAnnotation->getEndpointId() : '', implode(',', $asynchronous->getChannelName())),
            $message
        );
    }

    #[Around(precedence: 1000, pointcut: CommandHandler::class)]
    public function aroundCommandHandler(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $loggingGateway): mixed
    {
        $loggingGateway->info(
            'Executing Command Handler ' . $methodInvocation->getName(),
            $message
        );

        return $methodInvocation->proceed();
    }

    #[Around(precedence: 1000, pointcut: EventHandler::class)]
    public function aroundEventHandler(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $loggingGateway): mixed
    {
        $loggingGateway->info(
            'Executing Event Handler ' . $methodInvocation->getName(),
            $message
        );

        return $methodInvocation->proceed();
    }

    #[Around(precedence: 1000, pointcut: QueryHandler::class)]
    public function aroundQueryHandler(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $loggingGateway): mixed
    {
        $loggingGateway->info(
            'Executing Query Handler ' . $methodInvocation->getName(),
            $message
        );

        return $methodInvocation->proceed();
    }

    #[Around(precedence: 1000, pointcut: InternalHandler::class)]
    public function aroundMessageHandler(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $loggingGateway): mixed
    {
        $loggingGateway->info(
            'Executing Message Handler ' . $methodInvocation->getName(),
            $message
        );

        return $methodInvocation->proceed();
    }
}
