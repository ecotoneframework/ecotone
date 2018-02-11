<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder;

use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class MessageParameterConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Builder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageParameterConverterBuilder implements MethodParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * MessageParameterConverterBuilder constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return MessageParameterConverterBuilder
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
        return MessageParameterConverter::create($this->parameterName);
    }
}