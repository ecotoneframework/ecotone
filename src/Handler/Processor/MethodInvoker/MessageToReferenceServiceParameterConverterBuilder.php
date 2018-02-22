<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ReferenceServiceParameterConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToReferenceServiceParameterConverterBuilder implements MessageToParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $referenceName;

    /**
     * ReferenceServiceParameterConverterBuilder constructor.
     * @param string $parameterName
     * @param string $referenceName
     */
    private function __construct(string $parameterName, string $referenceName)
    {
        $this->parameterName = $parameterName;

        $this->initialize($referenceName);
    }

    /**
     * @param string $parameterName
     * @param string $referenceName
     * @param MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder
     * @return MessageToReferenceServiceParameterConverterBuilder
     */
    public static function create(string $parameterName, string $referenceName, MessageHandlerBuilderWithParameterConverters $messageHandlerBuilder) : self
    {
        $messageHandlerBuilder->registerRequiredReference($referenceName);
        return new self($parameterName, $referenceName);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageToParameterConverter
    {
         return MessageToReferenceServiceParameterConverter::create($this->parameterName, $referenceSearchService->findByReference($this->referenceName));
    }

    /**
     * @param string $referenceName
     */
    private function initialize(string $referenceName) : void
    {
        $this->referenceName = $referenceName;
    }
}