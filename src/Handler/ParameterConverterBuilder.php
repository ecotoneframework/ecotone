<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

/**
 * Interface MethodParameterConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
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