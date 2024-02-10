<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagating;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

final class FakeLoggingService
{
    private array $beforeLogHeaders = [];
    private array $afterLogHeaders = [];

    #[Around(pointcut: PropagatingGateway::class)]
    public function intercept(MethodInvocation $methodInvocation, Message $message, #[Reference] FakeLoggingGateway $fakeLoggingGateway): mixed
    {
        $fakeLoggingGateway->logBefore($message);
        $result = $methodInvocation->proceed();
        $fakeLoggingGateway->logAfter($message);

        return $result;
    }

    #[CommandHandler('beforeLog')]
    public function beforeLog(#[Headers] array $headers): void
    {
        $this->beforeLogHeaders = $headers;
    }

    #[CommandHandler('afterLog')]
    public function afterLog(#[Headers] array $headers): void
    {
        $this->afterLogHeaders = $headers;
    }

    #[QueryHandler('getBeforeLogHeaders')]
    public function getBeforeLogHeaders(): array
    {
        return $this->beforeLogHeaders;
    }

    #[QueryHandler('getAfterLogHeaders')]
    public function getAfterLogHeaders(): array
    {
        return $this->afterLogHeaders;
    }
}
