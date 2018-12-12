<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class CallAggregateService
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallAggregateService
{
    /**
     * @var array|ParameterConverterBuilder[]
     */
    private $messageToParameterConverters;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param ChannelResolver $channelResolver
     * @param array|ParameterConverterBuilder[] $messageToParameterConverters
     * @param ReferenceSearchService $referenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct(ChannelResolver $channelResolver, array $messageToParameterConverters, ReferenceSearchService $referenceSearchService)
    {
        Assert::allInstanceOfType($messageToParameterConverters, ParameterConverter::class);

        $this->messageToParameterConverters = $messageToParameterConverters;
        $this->channelResolver = $channelResolver;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws MessageHandlingException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function call(Message $message) : Message
    {
        $aggregate = $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_OBJECT)
                            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
                            : null;
        $methodInvoker = MethodInvoker::createWithBuiltParameterConverters(
            $aggregate ? $aggregate : $message->getHeaders()->get(AggregateMessage::CLASS_NAME),
            $message->getHeaders()->get(AggregateMessage::METHOD_NAME),
            $this->messageToParameterConverters,
            $this->referenceSearchService
        );

        $resultMessage = MessageBuilder::fromMessage($message);
        try {
            $result = $methodInvoker->processMessage($message);

            if (!$aggregate) {
                $resultMessage = $resultMessage
                    ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $result);
            }
        } catch (\Throwable $e) {
            throw MessageHandlingException::fromOtherException($e, $message);
        }

        if (!is_null($result)) {
            $resultMessage = $resultMessage
                ->setPayload($result);
        }

        return $resultMessage
                ->build();
    }
}