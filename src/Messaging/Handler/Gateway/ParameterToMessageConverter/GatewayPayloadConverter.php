<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class PayloadMessageParameter
 * @package Ecotone\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
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