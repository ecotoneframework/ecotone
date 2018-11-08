<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCallRegistry;
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
class EnricherBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var string
     */
    private $requestChannelName;
    /**
     * @var string|null
     */
    private $requestPayloadExpression;
    /**
     * @var PropertyEditorBuilder[]
     */
    private $propertyEditors;
    /**
     * @var string[]
     */
    private $requestHeaders = [];

    /**
     * EnricherBuilder constructor.
     *
     * @param PropertyEditorBuilder[] $setters
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(array $setters)
    {
        Assert::allInstanceOfType($setters, PropertyEditorBuilder::class);

        $this->propertyEditors   = $setters;
    }

    /**
     * @param PropertyEditor[] $setterBuilders
     *
     * @return EnricherBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(array $setterBuilders): self
    {
        return new self($setterBuilders);
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
     * @param array $headers
     * @return EnricherBuilder
     */
    public function withRequestHeaders(array $headers) : self
    {
        foreach ($headers as $headerName => $value) {
            $this->withRequestHeader($headerName, $value);
        }

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
        if (empty($this->propertyEditors)) {
            throw ConfigurationException::create("Can't configure enricher with no property setters");
        }

        $propertySetters = [];
        foreach ($this->propertyEditors as $setterBuilder) {
            $propertySetters[] = $setterBuilder->build($referenceSearchService);
        }

        $gateway = null;
        if ($this->requestChannelName) {
            /** @var EnrichGateway $gateway */
                $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), EnrichGateway::class, "execute", $this->requestChannelName)
                        ->build($referenceSearchService, $channelResolver);
        }

        $internalEnrichingService = new InternalEnrichingService(
            $gateway,
            $referenceSearchService->get(ExpressionEvaluationService::REFERENCE),
            $referenceSearchService,
            $referenceSearchService->get(ConversionService::REFERENCE_NAME),
            $propertySetters,
            $this->requestPayloadExpression,
            $this->requestHeaders
        );

        return new Enricher(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                MethodInvoker::createWith(
                    $internalEnrichingService,
                    "enrich",
                    [],
                    $referenceSearchService
                ),
                $channelResolver,
                false
            )
        );
    }
}