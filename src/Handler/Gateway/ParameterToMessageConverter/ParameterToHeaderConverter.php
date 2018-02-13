<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class HeaderMessageParameter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ParameterToHeaderConverter implements ParameterToMessageConverter
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
     * @return ParameterToHeaderConverter
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MethodArgument $methodArgument): bool
    {
        return $this->parameterName == $methodArgument->getParameterName();
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                    ->setHeader($this->headerName, $methodArgument->value());
    }
}