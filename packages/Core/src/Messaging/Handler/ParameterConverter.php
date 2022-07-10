<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\InvalidMessageHeaderException;
use Ecotone\Messaging\Message;

/**
 * Class MethodArgument
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterConverter
{
    /**
     * @param InterfaceToCall $interfaceToCall
     * @param InterfaceParameter $relatedParameter
     * @param Message $message
     * @param object[] $endpointAnnotations
     * @return mixed
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations);

    /**
     * @param InterfaceParameter $parameter
     * @return bool
     */
    public function isHandling(InterfaceParameter $parameter) : bool;
}