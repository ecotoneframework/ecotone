<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;

/**
 * Class PayloadEnricherBuilder
 * @package Ecotone\Messaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    private ?string $requestChannelName = null;
    private ?string $requestPayloadExpression = null;
    /**
     * @var PropertyEditorBuilder[]
     */
    private array $propertyEditors;
    /**
     * @var string[]
     */
    private array $requestHeaders = [];

    /**
     * EnricherBuilder constructor.
     *
     * @param PropertyEditorBuilder[] $setters
     * @throws \Ecotone\Messaging\MessagingException
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
     * @throws \Ecotone\Messaging\MessagingException
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
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [
            $interfaceToCallRegistry->getFor(InternalEnrichingService::class, "enrich"),
            $interfaceToCallRegistry->getFor(EnrichGateway::class, "execute")
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(InternalEnrichingService::class, "enrich");
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

        /** @var InterfaceToCallRegistry $interfaceToCallRegistry */
        $interfaceToCallRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        return new Enricher(
            RequestReplyProducer::createRequestAndReply(
                $this->outputMessageChannelName,
                MethodInvoker::createWith(
                    $interfaceToCallRegistry->getFor($internalEnrichingService, "enrich"),
                    $internalEnrichingService,
                    [],
                    $referenceSearchService,
                    $channelResolver,
                    $this->orderedAroundInterceptors,
                    $this->getEndpointAnnotations()
                ),
                $channelResolver,
                false
            )
        );
    }
}