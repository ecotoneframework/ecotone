<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverterBuilder;

/**
 * Class PayloadToMessageConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterToPayloadConverterBuilder implements ParameterToMessageConverterBuilder
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
        return ParameterToPayloadConverter::create($this->parameterName);
    }
}