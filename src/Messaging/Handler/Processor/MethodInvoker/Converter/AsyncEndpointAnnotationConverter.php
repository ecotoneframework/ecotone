<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Endpoint\PollingConsumer\AsyncEndpointAnnotationContext;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * licence Enterprise
 */
class AsyncEndpointAnnotationConverter implements ParameterConverter
{
    public function __construct(
        private AsyncEndpointAnnotationContext $context,
        private string $attributeClassName,
    ) {
    }

    public function getArgumentFrom(Message $message): mixed
    {
        foreach ($this->context->getCurrentAnnotations() as $annotation) {
            if ($annotation instanceof $this->attributeClassName) {
                return $annotation;
            }
        }

        return null;
    }
}
