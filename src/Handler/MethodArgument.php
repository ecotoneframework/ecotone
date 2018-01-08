<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\InvalidMessageHeaderException;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodArgument
{
    /**
     * @param Message $message
     * @return mixed
     * @throws InvalidMessageHeaderException
     */
    public function getFrom(Message $message);

    /**
     * @return string
     */
    public function getName() : string;
}