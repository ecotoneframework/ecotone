<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddExecutorId;

use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Modelling\Annotation\CommandHandler;

class AddExecutorId
{
    private string $executorId = "";

    /**
     * @CommandHandler("changeExecutorId")
     */
    public function addExecutorId(string $executorId) : void
    {
        $this->executorId = $executorId;
    }

    /**
     * @Before(pointcut="Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\Logger")
     */
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