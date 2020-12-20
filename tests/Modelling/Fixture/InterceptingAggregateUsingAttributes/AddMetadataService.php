<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Modelling\Annotation\CommandHandler;

class AddMetadataService
{
    #[Before(changeHeaders: true)]
    public function addHandlerInfo(CommandHandler $commandHandler) : array
    {
        return ["handlerInfo" => $commandHandler->getInputChannelName()];
    }

    #[Before(changeHeaders: true)]
    public function addMetadata(AddMetadata $addMetadata) : array
    {
        return [$addMetadata->getName() => $addMetadata->getValue()];
    }

    private string $userId;

    #[CommandHandler("addCurrentUserId")]
    public function addUserId(string $userId) : void
    {
        $this->userId = $userId;
    }

    #[Before(pointcut: Basket::class)]
    public function addCurrentUser(array $payload)
    {
        return array_merge($payload, ["userId" => $this->userId]);
    }
}