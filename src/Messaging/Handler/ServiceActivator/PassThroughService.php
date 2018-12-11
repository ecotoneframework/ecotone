<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\ServiceActivator;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class PassThroughService
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class PassThroughService
{
    /**
     * @var MessageProcessor
     */
    private $methodInvoker;

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