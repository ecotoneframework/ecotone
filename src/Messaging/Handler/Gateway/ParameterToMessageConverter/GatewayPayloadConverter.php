<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class PayloadMessageParameter
 * @package Ecotone\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayPayloadConverter implements GatewayParameterConverter
{
    public function __construct(private string $parameterName, private ?Type $parameterType)
    {
    }

    /**
     * @param string $parameterName
     * @return GatewayPayloadConverter
     */
    public static function create(InterfaceParameter $parameter): self
    {
        return new self($parameter->getName(), self::getParameterTypeDescriptor($parameter));
    }

    public static function getParameterTypeDescriptor(InterfaceParameter $parameter): ?Type
    {
        $type = $parameter->getTypeDescriptor();
        if ($type->isUnionType() || $type->isCompoundObjectType() || $type->isAnything()) {
            return null;
        } else {
            /** @var Type $type */
            return $type;
        }
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(?MethodArgument $methodArgument): bool
    {
        return $methodArgument && ($this->parameterName === $methodArgument->getParameterName());
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(?MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        Assert::notNull($methodArgument, 'Gateway header converter can only be called with method argument');

        $type = $this->parameterType
            ? $this->parameterType->getTypeHint()
            : Type::createFromVariable($methodArgument->value())->getTypeHint();

        $messageBuilder->setContentTypeIfAbsent(MediaType::createApplicationXPHPWithTypeParameter($type));

        return $methodArgument->value() instanceof Message ? MessageBuilder::fromMessage($methodArgument->value()) : $messageBuilder->setPayload($methodArgument->value());
    }
}
