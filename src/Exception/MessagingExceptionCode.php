<?php

namespace Messaging\Exception;

/**
 * Interface ContainsMessagingExceptionCode
 * @package Messaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessagingExceptionCode
{
    const INVALID_MESSAGE_HEADER = 100;

    const MESSAGE_SEND_EXCEPTION = 200;
}