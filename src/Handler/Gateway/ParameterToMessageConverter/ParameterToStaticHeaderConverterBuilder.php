<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverterBuilder;

/**
 * Class StaticHeaderToMessageConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterToStaticHeaderConverterBuilder implements ParameterToMessageConverterBuilder
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var string
     */
    private $headerValue;

    /**
     * StaticHeaderMessageArgumentConverter constructor.
     * @param string $headerName
     * @param string $headerValue
     */
    private function __construct(string $headerName, string $headerValue)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    /**
     * @param string $headerName
     * @param string $headerValue
     * @return self
     */
    public static function create(string $headerName, string $headerValue) : self
    {
        return new self($headerName, $headerValue);
    }

    /**
     * @inheritDoc
     */
    public function build(): ParameterToMessageConverter
    {
        return ParameterToStaticHeaderConverter::create($this->headerName, $this->headerValue);
    }
}