<?php

namespace Messaging\Handler;

use Messaging\InvalidMessageHeaderException;
use Messaging\Message;

/**
 * Class MethodArgument
 * @package Messaging\Handler\ServiceActivator
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