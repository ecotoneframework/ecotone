<?php

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Interface MethodParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageToParameterConverterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return MessageToParameterConverter
     */
    public function build(ReferenceSearchService $referenceSearchService) : MessageToParameterConverter;
}