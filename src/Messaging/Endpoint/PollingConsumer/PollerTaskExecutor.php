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


    public function __construct(string $pollableChannelName, PollableChannel $pollableChannel, NonProxyGateway $entrypointGateway)
    {
        $this->pollableChannel = $pollableChannel;
        $this->entrypointGateway = $entrypointGateway;
        $this->pollableChannelName = $pollableChannelName;
    }

    public function execute(): void
    {
        try {
            $message = $this->pollableChannel->receive();
        }catch (\Throwable $exception) {
            throw new ChannelException("Can't pool message from {$this->pollableChannelName} error happen.", 0, $exception);
        }

        if ($message) {
            $message = MessageBuilder::fromMessage($message)
                ->setHeader(MessageHeaders::POLLED_CHANNEL, $this->pollableChannel)
                ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, $this->pollableChannelName)
                ->build();

            $this->entrypointGateway->execute([$message]);
        }
    }
}