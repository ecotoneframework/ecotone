<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddNotificationTimestamp;

use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\Logger;

class AddNotificationTimestamp
{
    private $currentTime;

    #[CommandHandler("changeCurrentTime")]
    public function setTime(string $currentTime) : void
    {
        $this->currentTime = $currentTime;
    }

    #[After(pointcut: Logger::class, changeHeaders: true)]
    public function add(array $events, array $metadata) : array
    {
        return array_merge(
            $metadata,
            ["notificationTimestamp" => $this->currentTime]
        );
    }
}