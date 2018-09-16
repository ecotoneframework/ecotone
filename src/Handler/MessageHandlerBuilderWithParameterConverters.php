<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

/**
 * Interface MessageHandlerBuilderWithParameterConverters
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilderWithParameterConverters extends MessageHandlerBuilder
{
    /**
     * @param string $referenceName
     * @return static
     */
    public function registerRequiredReference(string $referenceName);

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