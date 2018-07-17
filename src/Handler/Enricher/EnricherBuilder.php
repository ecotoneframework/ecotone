<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
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
class EnricherBuilder implements MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $inputChannelName = "";
    /**
     * @var string
     */
    private $outputChannelName = "";
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var string|null
     */
    private $requestPayloadExpression;
    /**
     * @var SetterBuilder[]
     */
    private $setterBuilders;
    /**
     * @var string[]
     */
    private $requestHeaders = [];

    /**
     * EnricherBuilder constructor.
     *
     * @param SetterBuilder[] $setters
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(array $setters)
    {
        Assert::allInstanceOfType($setters, SetterBuilder::class);

        $this->setterBuilders   = $setters;
    }

    /**
     * @param Setter[] $setterBuilders
     *
     * @return EnricherBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(array $setterBuilders): self
    {
        return new self($setterBuilders);
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
    public function withInputChannelName(string $inputChannelName): self
    {
        $this->inputChannelName = $inputChannelName;

        return $this;
    }

    /**
     * @param string $requestChannelName
     *
     * @return EnricherBuilder
     */
    public function withRequestMessageChannel(string $requestChannelName) : self
    {
        $this->requestChannelName = $requestChannelName;

        return $this;
    }

    /**
     * @param string $requestPayloadExpression
     *
     * @return EnricherBuilder
     */
    public function withRequestPayloadExpression(string $requestPayloadExpression) : self
    {
        $this->requestPayloadExpression = $requestPayloadExpression;

        return $this;
    }

    /**
     * @param string $outputChannelName
     *
     * @return EnricherBuilder
     */
    public function withOutputMessageChannel(string $outputChannelName) : self
    {
        $this->outputChannelName = $outputChannelName;

        return $this;
    }

    /**
     * @param string $headerName
     * @param string $value
     *
     * @return EnricherBuilder
     */
    public function withRequestHeader(string $headerName, string $value) : self
    {
        $this->requestHeaders[$headerName] = $value;

        return $this;
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
        if (empty($this->setterBuilders)) {
            throw ConfigurationException::create("Can't configure enricher with no property setters");
        }

        $propertySetters = [];
        foreach ($this->setterBuilders as $setterBuilder) {
            $propertySetters[] = $setterBuilder->build($referenceSearchService);
        }

        $gateway = null;
        if ($this->requestChannelName) {
            /** @var EnrichGateway $gateway */
                $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), EnrichGateway::class, "execute", $this->requestChannelName)
                        ->withMillisecondTimeout(1)
                        ->build($referenceSearchService, $channelResolver);
        }

        $internalEnrichingService = new InternalEnrichingService($gateway, $referenceSearchService->findByReference(ExpressionEvaluationService::REFERENCE), $propertySetters, $this->requestPayloadExpression, $this->requestHeaders);

        return new Enricher(
            RequestReplyProducer::createRequestAndReply(
                $this->outputChannelName,
                MethodInvoker::createWith($internalEnrichingService, "enrich", [], $referenceSearchService),
                $channelResolver,
                false
            )
        );
    }
}