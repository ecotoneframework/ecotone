<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class HeaderParameterConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToHeaderParameterConverterBuilder implements MessageToParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var bool
     */
    private $isRequired = true;

    /**
     * HeaderParameterConverterBuilder constructor.
     * @param string $parameterName
     * @param string $headerName
     */
    private function __construct(string $parameterName, string $headerName)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     *
     * @return MessageToHeaderParameterConverterBuilder
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @param bool $isRequired
     *
     * @return MessageToHeaderParameterConverterBuilder
     */
    public function setRequired(bool $isRequired) : self
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MessageToParameterConverter
    {
        return MessageToHeaderParameterConverter::create($this->parameterName, $this->headerName, $this->isRequired);
    }
}