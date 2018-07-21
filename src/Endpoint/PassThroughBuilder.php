<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class PassThroughBuilder - If return type of interceptor is void, then it will be pass through when no exception arrived
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PassThroughBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @var
     */
    private $internalInterceptorMessageHandler;

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        // TODO: Implement build() method.
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {

    }
}