<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ramsey\Uuid\Uuid;

/**
 * Class PayloadEnricherBuilder
 * @package Ecotone\Messaging\Handler\Transformer
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
    public function withRequestMessageChannel(string $requestChannelName): self
    {
        $this->requestChannelName = $requestChannelName;

        return $this;
    }

    /**
     * @param string $requestPayloadExpression
     *
     * @return EnricherBuilder
     */
    public function withRequestPayloadExpression(string $requestPayloadExpression): self
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
    public function withRequestHeader(string $headerName, string $value): self
    {
        $this->requestHeaders[$headerName] = $value;

        return $this;
    }

    /**
     * @param array $headers
     * @return EnricherBuilder
     */
    public function withRequestHeaders(array $headers): self
    {
        foreach ($headers as $headerName => $value) {
            $this->withRequestHeader($headerName, $value);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(InternalEnrichingService::class, 'enrich');
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        if (empty($this->propertyEditors)) {
            throw ConfigurationException::create("Can't configure enricher with no property setters");
        }

        $propertySetters = [];
        foreach ($this->propertyEditors as $setterBuilder) {
            $propertySetters[] = $setterBuilder->compile($builder);
        }

        $gateway = null;
        if ($this->requestChannelName) {
            $gatewayBuilder = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), EnrichGateway::class, 'execute', $this->requestChannelName);
            $gatewayBuilder->compile($builder);
            $gateway = $gatewayBuilder->registerProxy($builder);
        }

        $internalEnrichingService = new Definition(InternalEnrichingService::class, [
            $gateway,
            new Reference(ExpressionEvaluationService::REFERENCE),
            new Reference(ConversionService::REFERENCE_NAME),
            $propertySetters,
            $this->requestPayloadExpression,
            $this->requestHeaders,
        ]);

        return ServiceActivatorBuilder::createWithDefinition($internalEnrichingService, 'enrich')
                ->withEndpointId($this->getEndpointId())
                ->withInputChannelName($this->getInputMessageChannelName())
                ->withOutputMessageChannel($this->outputMessageChannelName)
                ->withEndpointAnnotations($this->getEndpointAnnotations())
                ->compile($builder);

    }
}
