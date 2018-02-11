<?php

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Interface MethodParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodParameterConverterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return MethodParameterConverter
     */
    public function build(ReferenceSearchService $referenceSearchService) : MethodParameterConverter;
}