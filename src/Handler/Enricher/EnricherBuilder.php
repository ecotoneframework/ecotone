<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
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
     * @var array|PropertySetterBuilder[]
     */
    private $propertySetterBuilders;

    /**
     * EnricherBuilder constructor.
     *
     * @param string $inputChannelName
     * @param PropertySetterBuilder[]  $propertySetterBuilders
     */
    private function __construct(string $inputChannelName, array $propertySetterBuilders)
    {
        $this->inputChannelName = $inputChannelName;
        $this->propertySetterBuilders = $propertySetterBuilders;
    }

    /**
     * @param string $inputChannelName
     * @param PropertySetterBuilder[]  $propertySetterBuilders
     *
     * @return EnricherBuilder
     */
    public static function create(string $inputChannelName, array $propertySetterBuilders) : self
    {
        return new self($inputChannelName, $propertySetterBuilders);
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
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        if (empty($this->propertySetterBuilders)) {
            throw ConfigurationException::create("Can't configure enricher with no property setters");
        }

//        $requestGateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), EnrichReferenceService::class, "execute", $this->requestChannelName)
//                            ->build($referenceSearchService, $channelResolver);

//        $messageProcessor = MethodInvoker::createWith($requestGateway, "execute", []);

        return Enricher::create($messageProcessor, $channelResolver, $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE));
    }
}