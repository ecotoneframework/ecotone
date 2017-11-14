<?php

namespace Messaging\Handler\Gateway\MethodParameterConverter;

use Messaging\Handler\Gateway\MethodArgument;
use Messaging\Handler\Gateway\MethodArgumentConverter;
use Messaging\Support\MessageBuilder;

/**
 * Class HeaderMessageParameter
 * @package Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderMessageArgumentConverter implements MethodArgumentConverter
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
     * @return HeaderMessageArgumentConverter
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @inheritDoc
     */
    public function hasParameterNameAs(MethodArgument $methodArgument): bool
    {
        return $this->parameterName == $methodArgument->getParameterName();
    }

    /**
     * @inheritDoc
     */
    public function hasParameterName(string $parameterName): bool
    {
        return $this->parameterName == $parameterName;
    }

    /**
     * @inheritDoc
     */
    public function parameterName(): string
    {
        return $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage($value, MessageBuilder $messageBuilder): MessageBuilder
    {
        return $messageBuilder
                    ->setHeader($this->headerName, $value);
    }
}