<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageFilterBuilder
 * @package Ecotone\Messaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFilterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverters = [];
    /**
     * @var string[]
     */
    private array $requiredReferences = [];
    private string|object $referenceNameOrObject;
    private string $methodName;
    private ?string $discardChannelName = null;
    private bool $throwExceptionOnDiscard = false;

    private function __construct(string|object $referenceName, string $methodName)
    {
        $this->referenceNameOrObject     = $referenceName;
        $this->methodName        = $methodName;

        $this->initialize();
    }

    public static function createWithReferenceName(string $referenceName, string $methodName): self
    {
        return new self($referenceName, $methodName);
    }

    public static function createWithDirectObject(object $referenceObject, string $methodName): self
    {
        return new self($referenceObject, $methodName);
    }

    /**
     * @param bool|null $defaultResultWhenHeaderIsMissing When no presented exception will be thrown on missing header
     */
    public static function createBoolHeaderFilter(string $headerName, ?bool $defaultResultWhenHeaderIsMissing = null): self
    {
        return new self(new BoolHeaderBasedFilter($headerName, $defaultResultWhenHeaderIsMissing), 'filter');
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        $messageFilter = $interfaceToCallRegistry->getFor(MessageFilter::class, 'handle');

        return is_string($this->referenceNameOrObject)
                    ? [$interfaceToCallRegistry->getForReferenceName($this->referenceNameOrObject, $this->methodName), $messageFilter]
                    : [$interfaceToCallRegistry->getFor($this->referenceNameOrObject, $this->methodName), $messageFilter];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return is_string($this->referenceNameOrObject)
            ? $interfaceToCallRegistry->getForReferenceName($this->referenceNameOrObject, $this->methodName)
            : $interfaceToCallRegistry->getFor($this->referenceNameOrObject, $this->methodName);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        $this->parameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->parameterConverters;
    }

    /**
     * @param string $discardChannelName
     *
     * @return MessageFilterBuilder
     */
    public function withDiscardChannelName(string $discardChannelName): self
    {
        $this->discardChannelName = $discardChannelName;

        return $this;
    }

    /**
     * @param bool $throwOnDiscard
     *
     * @return MessageFilterBuilder
     */
    public function withThrowingExceptionOnDiscard(bool $throwOnDiscard): self
    {
        $this->throwExceptionOnDiscard = $throwOnDiscard;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $messageSelector = is_object($this->referenceNameOrObject) ? $this->referenceNameOrObject : $referenceSearchService->get($this->referenceNameOrObject);

        /** @var InterfaceToCall $interfaceToCall */
        $interfaceToCall = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME)->getFor($messageSelector, $this->methodName);
        if (! $interfaceToCall->hasReturnValueBoolean()) {
            $class = get_class($messageSelector);
            throw InvalidArgumentException::create("Object with reference {$class} should return bool for method {$this->methodName} while using Message Filter");
        }

        $discardChannel = $this->discardChannelName ? $channelResolver->resolve($this->discardChannelName) : null;
        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        $serviceActivatorBuilder = ServiceActivatorBuilder::createWithDirectReference(
            new MessageFilter(
                MethodInvoker::createWith(
                    $interfaceToCallRegistry->getFor($messageSelector, $this->methodName),
                    $messageSelector,
                    $this->parameterConverters,
                    $referenceSearchService,
                    $channelResolver,
                    $this->orderedAroundInterceptors,
                    $this->getEndpointAnnotations()
                ),
                $discardChannel,
                $this->throwExceptionOnDiscard
            ),
            'handle'
        )
            ->withInputChannelName($this->inputMessageChannelName)
            ->withOutputMessageChannel($this->outputMessageChannelName);

        return $serviceActivatorBuilder->build($channelResolver, $referenceSearchService);
    }


    private function initialize(): void
    {
        if (is_string($this->referenceNameOrObject)) {
            $this->requiredReferences[] = $this->referenceNameOrObject;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Message filter - %s:%s with name `%s` for input channel `%s`', $this->referenceNameOrObject, $this->methodName, $this->getEndpointId(), $this->inputMessageChannelName);
    }
}
