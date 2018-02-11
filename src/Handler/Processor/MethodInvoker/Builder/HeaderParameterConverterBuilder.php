<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder;

use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class HeaderParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderParameterConverterBuilder implements MethodParameterConverterBuilder
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
     * HeaderParameterConverterBuilder constructor.
     * @param string $parameterName
     * @param string $headerName
     */
    private function __construct(string $parameterName, string $headerName)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
    }

    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MethodParameterConverter
    {
        return HeaderParameterConverter::create($this->parameterName, $this->headerName);
    }
}