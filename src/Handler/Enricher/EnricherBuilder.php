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
use SimplyCodedSoftware\IntegrationMessaging\Handler\RequestReplyProducer;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

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
     * @var SetterBuilder[]
     */
    private $propertySetterBuilders;
    /**
     * @var HeaderSetterBuilder[]
     */
    private $headerSetterBuilders;

    /**
     * EnricherBuilder constructor.
     *
     * @param string $inputChannelName
     */
    private function __construct(string $inputChannelName)
    {
        $this->inputChannelName = $inputChannelName;
    }

    /**
     * @param string $inputChannelName
     *
     * @return EnricherBuilder
     */
    public static function create(string $inputChannelName) : self
    {
        return new self($inputChannelName);
    }

    /**
     * @param Setter[] $propertySetterBuilders
     * @return EnricherBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function withPropertySetters(array $propertySetterBuilders) : self
    {
        Assert::allInstanceOfType($propertySetterBuilders, SetterBuilder::class);
        $this->propertySetterBuilders = $propertySetterBuilders;

        return $this;
    }

    /**
     * @param array $headerSetterBuilders
     * @return EnricherBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function withHeaderSetters(array $headerSetterBuilders) : self
    {
        Assert::allInstanceOfType($headerSetterBuilders, HeaderSetterBuilder::class);
        $this->headerSetterBuilders = $headerSetterBuilders;

        return $this;
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
        if (empty($this->propertySetterBuilders) && empty($this->headerSetterBuilders)) {
            throw ConfigurationException::create("Can't configure enricher with no property setters");
        }

        $propertySetters = [];
        foreach ($this->propertySetterBuilders as $propertySetterBuilder) {
            $propertySetters[] = $propertySetterBuilder->build($referenceSearchService);
        }

        $internalEnrichingService = new InternalEnrichingService($propertySetters);
        return new Enricher(
            RequestReplyProducer::createRequestAndReplyFromHeaders(
                MethodInvoker::createWith($internalEnrichingService, "enrich", []),
                $channelResolver,
                false
            )
        );
    }
}