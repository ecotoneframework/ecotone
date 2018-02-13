<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class HeaderToMessageConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterToHeaderConverterBuilder implements ParameterToMessageConverterBuilder
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
     * HeaderMessageParameter constructor.
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
     * @return self
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }


    /**
     * @inheritDoc
     */
    public function build(): ParameterToMessageConverter
    {
        return ParameterToHeaderConverter::create($this->parameterName, $this->headerName);
    }
}