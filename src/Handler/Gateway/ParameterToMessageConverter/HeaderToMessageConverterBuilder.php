<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class HeaderToMessageConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderToMessageConverterBuilder implements ParameterToMessageConverterBuilder
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
        return HeaderToMessageConverter::create($this->parameterName, $this->headerName);
    }
}