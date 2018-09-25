<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\InvalidMessageHeaderException;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
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