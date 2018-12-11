<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class StaticHeaderMessageArgumentConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class GatewayHeaderValueConverter implements GatewayParameterConverter
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var mixed
     */
    private $headerValue;

    /**
     * StaticHeaderMessageArgumentConverter constructor.
     * @param string $headerName
     * @param mixed $headerValue
     */
    private function __construct(string $headerName, $headerValue)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    /**
     * @param string $headerName
     * @param mixed $headerValue
     * @return GatewayHeaderValueConverter
     */
    public static function create(string $headerName, $headerValue) : self
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