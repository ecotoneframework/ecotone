<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
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
        $messageBuilder->setContentTypeIfAbsent(MediaType::createApplicationXPHPObjectWithTypeParameter(
            $methodArgument->getInterfaceParameter()->getTypeDescriptor()->isUnionType()
                ? TypeDescriptor::createFromVariable($methodArgument->value())->getTypeHint()
                : $methodArgument->getInterfaceParameter()->getTypeHint()
        ));

        return $methodArgument->value() instanceof Message ? MessageBuilder::fromMessage($methodArgument->value()) : $messageBuilder->setPayload($methodArgument->value());
    }
}