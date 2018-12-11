<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;

/**
 * Class TaskExecutorBridge
 * @package SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TaskExecutorBridge implements MessageHandler
{
    /**
     * @var TaskExecutor
     */
    private $taskExecutor;

    /**
     * TaskExecutorBridge constructor.
     * @param TaskExecutor $taskExecutor
     */
    public function __construct(TaskExecutor $taskExecutor)
    {
        $this->taskExecutor = $taskExecutor;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->taskExecutor->execute();
    }

    public function __toString()
    {
        return "Task Execution Bridge";
    }
}