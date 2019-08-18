<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\ContextualPollableChannel;
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
     * @var string
     */
    private $endpointId;
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
     * @param string $endpointId
     * @param PollableChannel $pollableChannel
     * @param EntrypointGateway $entrypointGateway
     */
    public function __construct(string $endpointId, PollableChannel $pollableChannel, EntrypointGateway $entrypointGateway)
    {
        $this->endpointId = $endpointId;
        $this->pollableChannel = $pollableChannel;
        $this->entrypointGateway = $entrypointGateway;
    }

    public function execute(): void
    {
        if ($this->pollableChannel instanceof ContextualPollableChannel) {
            $message = $this->pollableChannel->receiveWithEndpointId($this->endpointId);
        }else {
            $message = $this->pollableChannel->receive();
        }


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