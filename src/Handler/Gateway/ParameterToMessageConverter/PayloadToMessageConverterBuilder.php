<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverterBuilder;

/**
 * Class PayloadToMessageConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadToMessageConverterBuilder implements ParameterToMessageConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * PayloadMessageParameter constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return self
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function build(): ParameterToMessageConverter
    {
        return PayloadToMessageConverter::create($this->parameterName);
    }
}