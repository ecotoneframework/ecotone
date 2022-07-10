<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled;

use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\Presend;
use Ecotone\Messaging\Attribute\Poller;
use Ecotone\Messaging\Attribute\Scheduled;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class InterceptedScheduledExample
{
    private int $requestData = 0;

    #[Scheduled("handle", "scheduled.handler")]
    #[Poller(executionTimeLimitInMilliseconds: 1, handledMessageLimit: 1)]
    public function buy(): int
    {
        return 10;
    }

    #[ServiceActivator("handle")]
    public function handle(int $payload, array $metadata, MessagingEntrypoint $messagingEntrypoint) : void
    {
        if (isset($metadata["entrypoint"])) {
            $this->requestData = $payload;
        }else {
            $messagingEntrypoint->sendWithHeaders($payload, ["entrypoint" => true],"handle");
        }
    }

    #[ServiceActivator("getRequestedData")]
    public function getRequestedData() : int
    {
        return $this->requestData;
    }

    #[Presend(pointcut: InterceptedScheduledExample::class . "::" . "handle")]
    public function beforeSend(int $payload) : int
    {
        return $payload * 2;
    }

    #[Before(pointcut: InterceptedScheduledExample::class . "::" . "handle")]
    public function before(int $payload) : int
    {
        return $payload * 2;
    }
}