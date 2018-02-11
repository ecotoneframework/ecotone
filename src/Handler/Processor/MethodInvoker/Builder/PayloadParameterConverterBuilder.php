<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder;

use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class PayloadParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadParameterConverterBuilder implements MethodParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * PayloadParameterConverterBuilder constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return PayloadParameterConverterBuilder
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): MethodParameterConverter
    {
        return PayloadParameterConverter::create($this->parameterName);
    }
}