<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\InvalidMessageHeaderException;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodParameterConverter
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