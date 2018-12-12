<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class AggregateMessageConversionServiceBuilder
 * @package SimplyCodedSoftware\DomainModel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageConversionServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $messageClassNameToConvertTo;

    /**
     * AggregateMessageConversionServiceBuilder constructor.
     * @param string $messageClassNameToConvertTo
     */
    private function __construct(string $messageClassNameToConvertTo)
    {
        $this->messageClassNameToConvertTo = $messageClassNameToConvertTo;
    }

    /**
     * @param string $messageClassNameToConvertTo
     * @return AggregateMessageConversionServiceBuilder
     */
    public static function createWith(string $messageClassNameToConvertTo) : self
    {
        return new self($messageClassNameToConvertTo);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        Assert::isSubclassOf($conversionService, ConversionService::class, "Have you forgot to register " . ConversionService::REFERENCE_NAME . "?");

        return ServiceActivatorBuilder::createWithDirectReference(new AggregateMessageConversionService($conversionService, $this->messageClassNameToConvertTo), "convert")
                    ->withOutputMessageChannel($this->getOutputMessageChannelName())
                    ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [ConversionService::REFERENCE_NAME];
    }
}