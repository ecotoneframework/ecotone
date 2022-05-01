<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class CallAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallAggregateService
{
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverterBuilders;
    private ChannelResolver $channelResolver;
    private ReferenceSearchService $referenceSearchService;
    /**
     * @var AroundMethodInterceptor[]
     */
    private array $aroundMethodInterceptors;
    private bool $isCommandHandler;
    private bool $isEventSourced;
    private bool $isFactoryMethod;
    private InterfaceToCall $aggregateInterface;
    /**
     * @var PropertyReaderAccessor
     */
    private PropertyReaderAccessor $propertyReaderAccessor;
    /**
     * @var PropertyEditorAccessor
     */
    private PropertyEditorAccessor $propertyEditorAccessor;
    private ?string $aggregateVersionProperty;
    private bool $isAggregateVersionAutomaticallyIncreased;
    private ?string $aggregateMethodWithEvents;

    public function __construct(InterfaceToCall $interfaceToCall, bool $isEventSourced, ChannelResolver $channelResolver, array $parameterConverterBuilders, array $aroundMethodInterceptors, ReferenceSearchService $referenceSearchService, PropertyReaderAccessor $propertyReaderAccessor, PropertyEditorAccessor $propertyEditorAccessor, bool $isCommand, bool $isFactoryMethod, private EventSourcingHandlerExecutor $eventSourcingHandlerExecutor, ?string $aggregateVersionProperty, bool $isAggregateVersionAutomaticallyIncreased, ?string $aggregateMethodWithEvents)
    {
        Assert::allInstanceOfType($parameterConverterBuilders, ParameterConverterBuilder::class);
        Assert::allInstanceOfType($aroundMethodInterceptors, AroundInterceptorReference::class);

        $this->parameterConverterBuilders = $parameterConverterBuilders;
        $this->channelResolver = $channelResolver;
        $this->referenceSearchService = $referenceSearchService;
        $this->aroundMethodInterceptors = $aroundMethodInterceptors;
        $this->isCommandHandler = $isCommand;
        $this->isEventSourced = $isEventSourced;
        $this->isFactoryMethod = $isFactoryMethod;
        $this->aggregateInterface = $interfaceToCall;
        $this->aggregateVersionProperty = $aggregateVersionProperty;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->isAggregateVersionAutomaticallyIncreased = $isAggregateVersionAutomaticallyIncreased;
        $this->aggregateMethodWithEvents = $aggregateMethodWithEvents;
    }

    public function call(Message $message): ?Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        $aggregate = $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_OBJECT)
            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
            : null;

        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION)
            ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION)
            : null;
        if (is_null($versionBeforeHandling) && $this->aggregateVersionProperty) {
            if ($this->isFactoryMethod) {
                $versionBeforeHandling = 0;
            } else {
                $versionBeforeHandling = $this->propertyReaderAccessor->getPropertyValue(
                    PropertyPath::createWith($this->aggregateVersionProperty),
                    $aggregate
                );
                $versionBeforeHandling = is_null($versionBeforeHandling) ? 0 : $versionBeforeHandling;
            }

            $resultMessage = $resultMessage
                ->setHeader(AggregateMessage::TARGET_VERSION, $versionBeforeHandling);
        }

        $methodInvoker = MethodInvoker::createWith(
            $this->aggregateInterface,
            $aggregate ? $aggregate : $this->aggregateInterface->getInterfaceType()->toString(),
            $this->parameterConverterBuilders,
            $this->referenceSearchService,
            $this->channelResolver,
            $this->aroundMethodInterceptors,
            []
        );

        $result = $methodInvoker->processMessage($message);
        $resultType = TypeDescriptor::createFromVariable($result);
        if ($resultType->isIterable() && $this->aggregateInterface->getReturnType()->isCollection()) {
            $interfaceReturnType = $this->aggregateInterface->getReturnType();
            if ($interfaceReturnType->isUnionType()) {
                $resultType = TypeDescriptor::createCollection(TypeDescriptor::OBJECT);
            }else {
                $resultType = $interfaceReturnType;
            }
        }

        if ($this->isCommandHandler && $this->isEventSourcedWithInteralEventRecorded()) {
            $result = call_user_func([$this->isFactoryMethod ? $result : $aggregate, $this->aggregateMethodWithEvents]);
            $resultType = TypeDescriptor::createCollection(TypeDescriptor::OBJECT);
        }

        if ($this->isFactoryMethod) {
            if ($this->isEventSourced) {
                $aggregate = $this->eventSourcingHandlerExecutor->fill($result, null);
            } else {
                Assert::isSubclassOf($result, $this->aggregateInterface->getInterfaceName(), "{$this->aggregateInterface} should return instance aggregate");
                $aggregate = $result;
            }

            $resultMessage = $resultMessage->setHeader(AggregateMessage::AGGREGATE_OBJECT, $aggregate);
        }elseif ($this->isCommandHandler && $this->isEventSourced) {
            $resultMessage->setHeader(AggregateMessage::AGGREGATE_OBJECT, $this->eventSourcingHandlerExecutor->fill($result, $aggregate));
        }

        if (!is_null($result)) {
            $resultMessage = $resultMessage
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($resultType->toString()))
                ->setPayload($result);
        }

        if ($this->isCommandHandler || !is_null($result)) {
            return $resultMessage
                ->build();
        }

        return null;
    }

    /**
     * @return bool
     */
    private function isEventSourcedWithInteralEventRecorded(): bool
    {
        return $this->isEventSourced && $this->aggregateMethodWithEvents;
    }
}