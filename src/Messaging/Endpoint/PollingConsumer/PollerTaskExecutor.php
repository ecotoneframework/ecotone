<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Endpoint\StoppableConsumer;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class PollingConsumerTaskExecutor
 * @package Ecotone\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerTaskExecutor implements TaskExecutor
{
    /**
     * @var PollableChannel
     */
    private $pollableChannel;
    /**
     * @var EntrypointGateway
     */
    private $entrypointGateway;

    /**
     * PollingConsumerTaskExecutor constructor.
     * @param PollableChannel $pollableChannel
     * @param EntrypointGateway $entrypointGateway
     */
    public function __construct(PollableChannel $pollableChannel, EntrypointGateway $entrypointGateway)
    {
        $this->pollableChannel = $pollableChannel;
        $this->entrypointGateway = $entrypointGateway;
    }

    public function execute(): void
    {
        $message = $this->pollableChannel->receive();

        if ($message) {
            $acknowledgementCallback = NullAcknowledgementCallback::create();
            if ($message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
                $acknowledgementCallback = $message->getHeaders()->get(
                    $message->getHeaders()->get(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)
                );
            }

            try {
                $this->entrypointGateway->executeEntrypoint($message);

                if ($acknowledgementCallback->isAutoAck()) {
                    $acknowledgementCallback->accept();
                }
            }catch (\Throwable $e) {
                $acknowledgementCallback->requeue();
            }
        }
    }
}