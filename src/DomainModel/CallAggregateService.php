<?php

namespace Ecotone\DomainModel;

use Ecotone\DomainModel\Config\AggregateMessagingModule;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class CallAggregateService
 * @package Ecotone\DomainModel
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
     * @var bool
     */
    private $withFactoryRedirectOnFoundMethodName;
    /**
     * @var array|ParameterConverterBuilder[]
     */
    private $withFactoryRedirectOnFoundParameterConverters;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param ChannelResolver $channelResolver
     * @param array|ParameterConverterBuilder[] $messageToParameterConverters
     * @param AroundMethodInterceptor[] $aroundMethodInterceptors
     * @param ReferenceSearchService $referenceSearchService
     * @param string $withFactoryRedirectOnFoundMethodName
     * @param ParameterConverterBuilder[] $withFactoryRedirectOnFoundParameterConverters
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct(ChannelResolver $channelResolver, array $messageToParameterConverters, array $aroundMethodInterceptors, ReferenceSearchService $referenceSearchService,
                                string $withFactoryRedirectOnFoundMethodName, array $withFactoryRedirectOnFoundParameterConverters
    )
    {
        Assert::allInstanceOfType($messageToParameterConverters, ParameterConverter::class);

        $this->messageToParameterConverters = $messageToParameterConverters;
        $this->channelResolver = $channelResolver;
        $this->referenceSearchService = $referenceSearchService;
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
        $this->withFactoryRedirectOnFoundMethodName = $withFactoryRedirectOnFoundMethodName;
        $this->withFactoryRedirectOnFoundParameterConverters = $withFactoryRedirectOnFoundParameterConverters;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws MessageHandlingException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Handler\ReferenceNotFoundException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function call(Message $message) : Message
    {
        $aggregate = $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_OBJECT)
                            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
                            : null;

        if ($aggregate && $this->withFactoryRedirectOnFoundMethodName) {
            $methodInvoker = MethodInvoker::createWithBuiltParameterConverters(
                $aggregate,
                $this->withFactoryRedirectOnFoundMethodName,
                $this->withFactoryRedirectOnFoundParameterConverters,
                $this->referenceSearchService,
                $this->aroundMethodInterceptors,
                [],
                true
            );
        }else {
            $methodInvoker = MethodInvoker::createWithBuiltParameterConverters(
                $aggregate ? $aggregate : $message->getHeaders()->get(AggregateMessage::CLASS_NAME),
                $message->getHeaders()->get(AggregateMessage::METHOD_NAME),
                $this->messageToParameterConverters,
                $this->referenceSearchService,
                $this->aroundMethodInterceptors,
                [],
                true
            );
        }

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