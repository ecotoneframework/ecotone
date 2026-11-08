<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Modelling\Config\DatabaseTransaction\CommandsWithoutDatabaseTransaction;

/**
 * licence Apache-2.0
 */
final class CommandBusInstantRetryByDefault
{
    public function __construct(
        private InstantRetryInterceptor $instantRetry,
        private CommandsWithoutDatabaseTransaction $commandsWithoutDatabaseTransaction,
    ) {
    }

    public function retry(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $logger, ?PollingMetadata $pollingMetadata)
    {
        if ($this->commandsWithoutDatabaseTransaction->isRoutedToHandlerWithoutTransaction($message)) {
            return $methodInvocation->proceed();
        }

        return $this->instantRetry->retry($methodInvocation, $message, $logger, $pollingMetadata);
    }
}
