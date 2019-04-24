<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter;

use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class PayloadMessageParameter
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayPayloadConverter implements GatewayParameterConverter
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
     * @return GatewayPayloadConverter
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
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
        $messageBuilder->setContentTypeIfAbsent(MediaType::createApplicationXPHPObjectWithTypeParameter($methodArgument->getInterfaceParameter()->getTypeHint()));

        return $messageBuilder->setPayload($methodArgument->value());
    }
}