<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;

/**
 * Class AggregateMessageConversionServiceBuilder
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateIdentifierRetrevingServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    private ?ClassDefinition $messageClassNameToConvertTo;
    private ClassDefinition $aggregateClassName;
    private array $metadataIdentifierMapping;

    private function __construct(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo)
    {
        $this->messageClassNameToConvertTo = $messageClassNameToConvertTo;
        $this->aggregateClassName = $aggregateClassName;
        $this->metadataIdentifierMapping = $metadataIdentifierMapping;
    }

    public static function createWith(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo) : self
    {
        return new self($aggregateClassName, $metadataIdentifierMapping, $messageClassNameToConvertTo);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        Assert::isSubclassOf($conversionService, ConversionService::class, "Have you forgot to register " . ConversionService::REFERENCE_NAME . "?");

        return ServiceActivatorBuilder::createWithDirectReference(new AggregateIdentifierRetrevingService($conversionService, new PropertyReaderAccessor(), $this->aggregateClassName, $this->metadataIdentifierMapping, $this->messageClassNameToConvertTo), "convert")
                    ->withOutputMessageChannel($this->getOutputMessageChannelName())
                    ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(AggregateIdentifierRetrevingService::class, "convert")];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(AggregateIdentifierRetrevingService::class, "convert");
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [ConversionService::REFERENCE_NAME];
    }
}