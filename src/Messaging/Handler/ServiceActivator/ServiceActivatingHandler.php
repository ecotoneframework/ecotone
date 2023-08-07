<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ServiceActivator;

use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class ServiceActivator
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class ServiceActivatingHandler implements MessageHandler
{
    public function __construct(
        private RequestReplyProducer $requestReplyProducer,
        private bool $hasAroundMethodInterceptor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        /**
         * We could call handleWithPossibleAroundInterceptors, yet
         * this is used to make the stacktrace shorter and more readable
         */
        if ($this->hasAroundMethodInterceptor) {
            $this->requestReplyProducer->handleWithPossibleAroundInterceptors($message);

            return;
        }

        $this->requestReplyProducer->executeEndpointAndSendReply($message);
    }

    public function __toString()
    {
        return 'Service Activator - ' . $this->requestReplyProducer;
    }
}
