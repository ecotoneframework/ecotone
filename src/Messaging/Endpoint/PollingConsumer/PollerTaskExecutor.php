<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\InboundGatewayEntrypoint;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Handler\NonProxyGateway;
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
     * @var NonProxyGateway|InboundGatewayEntrypoint
     */
    private $entrypointGateway;
    /**
     * @var string
     */
    private $pollableChannelName;
    /**
     * @var bool
     */
    private $errorHandledInErrorChannel;


    public function __construct(string $endpointId, string $pollableChannelName, PollableChannel $pollableChannel, NonProxyGateway $entrypointGateway, bool $errorHandledInErrorChannel)
    {
        $this->endpointId = $endpointId;
        $this->pollableChannel = $pollableChannel;
        $this->entrypointGateway = $entrypointGateway;
        $this->pollableChannelName = $pollableChannelName;
        $this->errorHandledInErrorChannel = $errorHandledInErrorChannel;
    }

    public function execute(): void
    {
        $message = $this->pollableChannel->receive();

        if ($message) {
            $message = MessageBuilder::fromMessage($message)
                ->setHeader(MessageHeaders::POLLED_CHANNEL, $this->pollableChannel)
                ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, $this->pollableChannelName)
                ->build();

            $acknowledgementCallback = NullAcknowledgementCallback::create();
            if ($message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
                $acknowledgementCallback = $message->getHeaders()->get(
                    $message->getHeaders()->get(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)
                );
            }

            try {
                $this->entrypointGateway->execute([$message]);

                if ($acknowledgementCallback->isAutoAck()) {
                    $acknowledgementCallback->accept();
                }
            }catch (\Throwable $exception) {
                if ($this->errorHandledInErrorChannel) {
                    throw $exception;
                }

                $acknowledgementCallback->requeue();
            }
        }
    }
}