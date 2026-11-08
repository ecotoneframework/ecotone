<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Attribute\WithoutDatabaseTransaction;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Modelling\Config\DatabaseTransaction\ManagesOwnTransaction;

use function in_array;

/**
 * licence Apache-2.0
 */
final class AsynchronousEndpointInstantRetryByDefault
{
    /**
     * @param string[] $endpointsWithoutTransaction
     */
    public function __construct(
        private InstantRetryInterceptor $instantRetry,
        private bool $endpointsRunInDatabaseTransaction,
        private array $endpointsWithoutTransaction,
    ) {
    }

    public function retry(
        MethodInvocation $methodInvocation,
        Message $message,
        #[Reference] LoggingGateway $logger,
        ?PollingMetadata $pollingMetadata,
        ?WithoutDatabaseTransaction $withoutDatabaseTransaction = null,
        ?ManagesOwnTransaction $managesOwnTransaction = null,
    ) {
        if ($managesOwnTransaction === null && ! $this->runsInDatabaseTransaction($pollingMetadata, $withoutDatabaseTransaction)) {
            return $methodInvocation->proceed();
        }

        return $this->instantRetry->retry($methodInvocation, $message, $logger, $pollingMetadata);
    }

    private function runsInDatabaseTransaction(?PollingMetadata $pollingMetadata, ?WithoutDatabaseTransaction $withoutDatabaseTransaction): bool
    {
        return $this->endpointsRunInDatabaseTransaction
            && $withoutDatabaseTransaction === null
            && ! in_array($pollingMetadata?->getEndpointId(), $this->endpointsWithoutTransaction, true);
    }
}
