<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class PayloadEnricherBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $inputChannelName;
    /**
     * @var string
     */
    private $requestChannelName;

    /**
     * EnricherBuilder constructor.
     *
     * @param string $inputChannelName
     * @param string $requestChannelName
     */
    private function __construct(string $inputChannelName, string $requestChannelName)
    {
        $this->inputChannelName = $inputChannelName;
        $this->requestChannelName = $requestChannelName;
    }

    /**
     * @param string $inputChannelName
     * @param string $requestChannelName
     *
     * @return EnricherBuilder
     */
    public static function create(string $inputChannelName, string $requestChannelName) : self
    {
        return new self($inputChannelName, $requestChannelName);
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [
            ExpressionEvaluationService::REFERENCE
        ];
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $requestGateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), EnrichReferenceService::class, "execute", $this->requestChannelName)
                            ->build($referenceSearchService, $channelResolver);

        $messageProcessor = MethodInvoker::createWith($requestGateway, "execute", []);

        return Enricher::create($messageProcessor, $channelResolver, $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE));
    }
}