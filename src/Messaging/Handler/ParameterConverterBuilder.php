<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

/**
 * Interface MethodParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterConverterBuilder
{
    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ParameterConverter
     */
    public function build(ReferenceSearchService $referenceSearchService) : ParameterConverter;
}