<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Scheduling\TaskExecutor;

/**
 * Class TaskExecutorBridge
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter
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
}