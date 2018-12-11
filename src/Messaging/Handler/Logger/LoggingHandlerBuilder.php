<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Logger;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class LoggingHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingHandlerBuilder extends InputOutputMessageHandlerBuilder
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        return
            ServiceActivatorBuilder::createWithDirectReference(
                new LoggingHandler($referenceSearchService->get(ConversionService::REFERENCE_NAME)),
            "handle"
            )
                ->withPassThroughMessageOnVoidInterface(true)
                ->withOutputMessageChannel($this->outputMessageChannelName)
                ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}