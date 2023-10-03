<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * Class MessageToStaticValueParameterConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ValueConverter implements ParameterConverter
{
    public function __construct(private mixed $staticValue)
    {
    }

    public static function createWith(mixed $staticValue): self
    {
        return new self($staticValue);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): mixed
    {
        return $this->staticValue;
    }
}
