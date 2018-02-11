<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder;

use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceServiceParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ReferenceServiceParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceServiceParameterConverterBuilder implements MethodParameterConverterBuilder
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
     * @return ReferenceServiceParameterConverterBuilder
     */
    public static function create(string $parameterName, string $referenceName, MessageHandlerBuilder $messageHandlerBuilder) : self
    {
        return new self($parameterName, $referenceName, $messageHandlerBuilder);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MethodParameterConverter
    {
         return ReferenceServiceParameterConverter::create($this->parameterName, $referenceSearchService->findByReference($this->referenceName));
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