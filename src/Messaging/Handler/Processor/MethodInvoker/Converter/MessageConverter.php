<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * Class MessageArgument
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class MessageConverter implements ParameterConverter
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): Message
    {
        return $message;
    }
}
