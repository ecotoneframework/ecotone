<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class PassThroughService
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class PassThroughService
{
    /**
     * @var MethodInvoker
     */
    private $methodInvoker;

    /**
     * PassThroughService constructor.
     * @param MethodInvoker $methodInvoker
     */
    public function __construct(MethodInvoker $methodInvoker)
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