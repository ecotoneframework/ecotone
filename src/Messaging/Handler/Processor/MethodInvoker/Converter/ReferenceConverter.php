<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ParameterConverter;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
class ReferenceConverter implements ParameterConverter
{
    public function __construct(private object $service)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): mixed
    {
        return $this->service;
    }
}
