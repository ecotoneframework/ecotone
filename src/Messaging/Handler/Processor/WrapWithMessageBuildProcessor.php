<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor;


use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class WrapWithMessageProcessor Constructs reply message with correct content type
 * @package SimplyCodedSoftware\Messaging\Handler\Processor
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
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
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

        return MessageBuilder::fromMessage($message)
            ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter($this->interfaceToCall->getReturnType()->toString())->toString())
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