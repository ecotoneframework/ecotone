<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class MessageToStaticParameterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToStaticValueParameterConverterBuilder implements MessageToParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var mixed
     */
    private $staticValue;

    /**
     * MessageToStaticValueParameterConverterBuilder constructor.
     *
     * @param string $parameterName
     * @param mixed  $staticValue
     */
    private function __construct(string $parameterName, $staticValue)
    {
        $this->parameterName = $parameterName;
        $this->staticValue = $staticValue;
    }

    /**
     * @param string $parameterName
     * @param mixed  $staticValue
     *
     * @return MessageToStaticValueParameterConverterBuilder
     */
    public static function create(string $parameterName, $staticValue) : self
    {
        return new self($parameterName, $staticValue);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageToParameterConverter
    {
        return MessageToStaticValueParameterConverter::createWith($this->parameterName, $this->staticValue);
    }
}