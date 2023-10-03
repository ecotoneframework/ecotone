<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;

/**
 * Class ServiceReferenceParameterConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ReferenceConverter implements ParameterConverter
{
    public function __construct(private ReferenceSearchService $referenceSearchService, private string $serviceReference)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): object
    {
        return $this->referenceSearchService->get($this->serviceReference);
    }
}
