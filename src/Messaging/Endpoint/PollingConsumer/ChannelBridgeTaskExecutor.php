<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer;

use SimplyCodedSoftware\Messaging\Endpoint\EntrypointGateway;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;

/**
 * Class PollingConsumerTaskExecutor
 * @package SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChannelBridgeTaskExecutor implements TaskExecutor
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
            $this->entrypointGateway->execute($message);
        }
    }
}