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
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var MessageProcessor
     */
    private $messageProcessor;

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

    /**
     * @param $objectToInvokeOn
     * @param string $objectMethodName
     * @param MessageProcessor $messageProcessor
     * @param ReferenceSearchService $referenceSearchService
     * @return WrapWithMessageBuildProcessor
     * @throws \Ecotone\Messaging\Handler\ReferenceNotFoundException
     */
    public static function createWith($objectToInvokeOn, string $objectMethodName, MessageProcessor $messageProcessor, ReferenceSearchService $referenceSearchService)
    {
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        return new self($interfaceToCallRegistry->getFor($objectToInvokeOn, $objectMethodName), $messageProcessor);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
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
                        $foundUnionType = $type;
                        break;
                    }
                }
            }

            $returnType = $foundUnionType ?? $returnValueType;
        }

        return MessageBuilder::fromMessage($message)
            ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter($returnType->toString()))
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