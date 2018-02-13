<?php

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
     * @return void
     */
    public function registerRequiredReference(string $referenceName) : void;

    /**
     * @param array|MessageToParameterConverter[] $methodParameterConverterBuilders
     * @return void
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : void;
}