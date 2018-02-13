<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
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
     * @param MessageHandlerBuilder $messageHandlerBuilder
     */
    private function __construct(string $parameterName, string $referenceName, MessageHandlerBuilder $messageHandlerBuilder)
    {
        $this->parameterName = $parameterName;

        $this->initialize($messageHandlerBuilder, $referenceName);
    }

    /**
     * @param string $parameterName
     * @param string $referenceName
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessageToReferenceServiceParameterConverterBuilder
     */
    public static function create(string $parameterName, string $referenceName, MessageHandlerBuilder $messageHandlerBuilder) : self
    {
        return new self($parameterName, $referenceName, $messageHandlerBuilder);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageToParameterConverter
    {
         return MessageToReferenceServiceParameterConverter::create($this->parameterName, $referenceSearchService->findByReference($this->referenceName));
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @param string $referenceName
     */
    private function initialize(MessageHandlerBuilder $messageHandlerBuilder, string $referenceName) : void
    {
        $messageHandlerBuilder->registerRequiredReference($referenceName);
        $this->referenceName = $referenceName;
    }
}