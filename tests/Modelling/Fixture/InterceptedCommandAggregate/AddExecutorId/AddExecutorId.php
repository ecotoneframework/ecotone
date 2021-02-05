<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddExecutorId;

use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Modelling\Attribute\CommandHandler;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\Logger;

class AddExecutorId
{
    private string $executorId = "";

    #[CommandHandler("changeExecutorId")]
    public function addExecutorId(string $executorId) : void
    {
        $this->executorId = $executorId;
    }

    #[Before(pointcut: Logger::class)]
    public function add(array $payload) : array
    {
        if (isset($payload["executorId"])) {
            return $payload;
        }

        return array_merge(
            $payload,
            ["executorId" => $this->executorId]
        );
    }
}