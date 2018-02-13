<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\InvalidMessageHeaderException;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageToParameterConverter
{
    /**
     * @param Message $message
     * @return mixed
     * @throws InvalidMessageHeaderException
     */
    public function getArgumentFrom(Message $message);

    /**
     * @param \ReflectionParameter $reflectionParameter
     * @return bool
     */
    public function isHandling(\ReflectionParameter $reflectionParameter) : bool;
}