<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\InvalidMessageHeaderException;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterConverter
{
    /**
     * @param InterfaceParameter $relatedParameter
     * @param Message $message
     * @return mixed
     */
    public function getArgumentFrom(InterfaceParameter $relatedParameter, Message $message);

    /**
     * @param InterfaceParameter $parameter
     * @return bool
     */
    public function isHandling(InterfaceParameter $parameter) : bool;
}