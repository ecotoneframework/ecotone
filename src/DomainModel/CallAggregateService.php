<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
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
     * @var array|AroundMethodInterceptor[]
     */
    private $aroundMethodInterceptors;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param ChannelResolver $channelResolver
     * @param array|ParameterConverterBuilder[] $messageToParameterConverters
     * @param AroundMethodInterceptor[] $aroundMethodInterceptors
     * @param ReferenceSearchService $referenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct(ChannelResolver $channelResolver, array $messageToParameterConverters, array $aroundMethodInterceptors, ReferenceSearchService $referenceSearchService)
    {
        Assert::allInstanceOfType($messageToParameterConverters, ParameterConverter::class);

        $this->messageToParameterConverters = $messageToParameterConverters;
        $this->channelResolver = $channelResolver;
        $this->referenceSearchService = $referenceSearchService;
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws MessageHandlingException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
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
            $this->referenceSearchService,
            $this->aroundMethodInterceptors
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