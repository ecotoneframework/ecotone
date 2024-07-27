<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class WrapWithMessageProcessor Constructs reply message with correct content type
 * @package Ecotone\Messaging\Handler\Processor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class WrapWithMessageBuildProcessor implements MessageProcessor
{
    public function __construct(
        private MessageProcessor $messageProcessor,
        private bool $shouldChangeMessageHeaders,
        private string $interfaceToCallName,
        private Type $returnType,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function executeEndpoint(Message $message): ?Message
    {
        $result = $this->messageProcessor->executeEndpoint($message);

        if (is_null($result)) {
            return null;
        }

        if ($this->shouldChangeMessageHeaders) {
            Assert::isFalse($result instanceof Message, 'Message should not be returned when changing headers in ' . $this->interfaceToCallName);
            Assert::isTrue(is_array($result), 'Result should be an array when changing headers in ' . $this->interfaceToCallName);

            return MessageBuilder::fromMessage($message)
                ->setMultipleHeaders($result)
                ->build();
        }

        if ($result instanceof Message) {
            return $result;
        }

        $returnType = $this->getReturnTypeFromResult($result);

        return MessageBuilder::fromMessage($message)
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

    public function getMethodCall(Message $message): MethodCall
    {
        return $this->messageProcessor->getMethodCall($message);
    }

    public function getObjectToInvokeOn(): string|object
    {
        return $this->messageProcessor->getObjectToInvokeOn();
    }

    public function getMethodName(): string
    {
        return $this->messageProcessor->getMethodName();
    }

    public function __toString(): string
    {
        return (string)$this->messageProcessor;
    }
}
