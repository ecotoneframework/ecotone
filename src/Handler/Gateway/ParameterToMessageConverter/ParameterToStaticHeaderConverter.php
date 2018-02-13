<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class StaticHeaderMessageArgumentConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterToStaticHeaderConverter implements ParameterToMessageConverter
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
     * @return ParameterToStaticHeaderConverter
     */
    public static function create(string $headerName, string $headerValue) : self
    {
        return new self($headerName, $headerValue);
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                ->setHeader($this->headerName, $this->headerValue);
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MethodArgument $methodArgument): bool
    {
        return true;
    }
}