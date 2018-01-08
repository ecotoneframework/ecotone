<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgumentConverter;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class StaticHeaderMessageArgumentConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StaticHeaderMessageArgumentConverter implements MethodArgumentConverter
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
     * @return StaticHeaderMessageArgumentConverter
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
    public function hasParameterNameAs(MethodArgument $methodArgument): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasParameterName(string $parameterName): bool
    {
        return true;
    }
}