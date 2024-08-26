<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * @licence Apache-2.0
 */
class PayloadResultMessageConverter implements ResultToMessageConverter
{
    public function __construct(
        private Type $returnType,
    ) {
    }

    public function convertToMessage(Message $requestMessage, mixed $result): ?Message
    {
        if (is_null($result) || $result instanceof Message) {
            return $result;
        }

        $returnType = $this->getReturnTypeFromResult($result);

        return MessageBuilder::fromMessage($requestMessage)
            ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($returnType->toString()))
            ->setPayload($result)
            ->build();
    }

    private function getReturnTypeFromResult(mixed $result): TypeDescriptor
    {
        $returnValueType = TypeDescriptor::createFromVariable($result);
        $returnType = $this->returnType;
        if ($returnType->isUnionType()) {
            /** @var UnionTypeDescriptor $returnType */
            $foundUnionType = null;
            foreach ($returnType->getUnionTypes() as $type) {
                if ($type->equals($returnValueType)) {
                    $foundUnionType = $type;
                    break;
                }
            }
            if (! $foundUnionType) {
                foreach ($returnType->getUnionTypes() as $type) {
                    if ($type->isCompatibleWith($returnValueType)) {
                        if ($type->isCollection()) {
                            $collectionOf = $type->resolveGenericTypes();
                            $firstKey = array_key_first($result);
                            if (count($collectionOf) === 1 && ! is_null($firstKey)) {
                                if (! $collectionOf[0]->isCompatibleWith(TypeDescriptor::createFromVariable($result[$firstKey]))) {
                                    continue;
                                }
                            }
                        }
                        $foundUnionType = $type;
                        break;
                    }
                }
            }

            $returnType = $foundUnionType ?? $returnValueType;
        }

        return $returnType;
    }
}
