<?php

namespace Messaging;

/**
 * Interface ContainsMessagingExceptionCode
 * @package Messaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessagingExceptionCode
{
    const INVALID_MESSAGE_HEADER_EXCEPTION = 100;
    const MESSAGE_HEADER_NOT_AVAILABLE_EXCEPTION = 101;
    const INVALID_ARGUMENT_EXCEPTION = 102;
    const MESSAGING_SERVICE_NOT_AVAILABLE_EXCEPTION = 103;
    const CONFIGURATION_EXCEPTION = 104;
    const DESTINATION_RESOLUTION_EXCEPTION = 105;

    const MESSAGE_DELIVERY_EXCEPTION = 200;
    const MESSAGE_DISPATCHING_EXCEPTION = 201;
    const WRONG_HANDLER_AMOUNT_EXCEPTION = 201;
}