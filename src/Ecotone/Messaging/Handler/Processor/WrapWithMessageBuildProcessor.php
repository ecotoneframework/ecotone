<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor;


use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class WrapWithMessageProcessor Constructs reply message with correct content type
 * @package Ecotone\Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrapWithMessageBuildProcessor implements MessageProcessor
{
    private \Ecotone\Messaging\Handler\InterfaceToCall $interfaceToCall;
    private \Ecotone\Messaging\Handler\MessageProcessor $messageProcessor;

    /**
     * WrapWithMessageProcessor constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param MessageProcessor $messageProcessor
     */
    public function __construct(InterfaceToCall $interfaceToCall, MessageProcessor $messageProcessor)
    {
        $this->interfaceToCall = $interfaceToCall;
        $this->messageProcessor = $messageProcessor;
    }

    public static function createWith(InterfaceToCall $interfaceToCall, MessageProcessor $messageProcessor, ReferenceSearchService $referenceSearchService)
    {
        return new self($interfaceToCall, $messageProcessor);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message): ?\Ecotone\Messaging\Message
    {
        $result = $this->messageProcessor->processMessage($message);

        if (is_null($result)) {
            return null;
        }

        if ($result instanceof Message) {
            return $result;
        }

        $returnValueType = TypeDescriptor::createFromVariable($result);
        /** @var UnionTypeDescriptor $returnType */
        $returnType = $this->interfaceToCall->getReturnType();
        if ($returnType->isUnionType()) {
            $foundUnionType = null;
            foreach ($returnType->getUnionTypes() as $type) {
                if ($type->equals($returnValueType)) {
                    $foundUnionType = $type;
                    break;
                }
            }
            if (!$foundUnionType) {
                foreach ($returnType->getUnionTypes() as $type) {
                    if ($type->isCompatibleWith($returnValueType)) {
                        if ($type->isCollection()) {
                            $collectionOf = $type->resolveGenericTypes();
                            $firstKey = array_key_first($result);
                            if (count($collectionOf) === 1 && !is_null($firstKey)) {
                                if (!$collectionOf[0]->isCompatibleWith(TypeDescriptor::createFromVariable($result[$firstKey]))) {
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

        return MessageBuilder::fromMessage($message)
            ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($returnType->toString()))
            ->setPayload($result)
            ->build();
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string)$this->messageProcessor;
    }
}