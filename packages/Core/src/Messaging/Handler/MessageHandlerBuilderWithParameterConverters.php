<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

/**
 * Interface MessageHandlerBuilderWithParameterConverters
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilderWithParameterConverters extends MessageHandlerBuilder
{
    /**
     * @param array|ParameterConverterBuilder[] $methodParameterConverterBuilders
     * @return static
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders);

    /**
     * @return ParameterConverterBuilder[]
     */
    public function getParameterConverters() : array;
}