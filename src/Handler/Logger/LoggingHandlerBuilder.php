<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Logger;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class LoggingHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Logger
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