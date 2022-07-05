<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ServiceActivator;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Message;

/**
 * Class PassThroughService
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class PassThroughService
{
    private \Ecotone\Messaging\Handler\MessageProcessor $methodInvoker;

    /**
     * PassThroughService constructor.
     * @param MessageProcessor $methodInvoker
     */
    public function __construct(MessageProcessor $methodInvoker)
    {
        $this->methodInvoker = $methodInvoker;
    }

    /**
     * @param Message $message
     * @return Message
     */
    public function invoke(Message $message) : Message
    {
        $this->methodInvoker->processMessage($message);

        return $message;
    }
}