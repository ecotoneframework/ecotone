<?php declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Router;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class RouterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var string
     */
    private $handlerName;
    /**
     * @var string
     */
    private $inputMessageChannelName;
    /**
     * @var string
     */
    private $objectToInvokeReference;
    /**
     * @var object
     */
    private $objectToInvoke;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|MessageToParameterConverterBuilder[]
     */
    private $methodParameterConverters = [];
    /**
     * @var bool
     */
    private $resolutionRequired = true;
    /**
     * @var string[]
     */
    private $requiredReferenceNames = [];

    /**
     * RouterBuilder constructor.
     * @param string $handlerName
     * @param string $inputChannel
     * @param string $objectToInvokeReference
     * @param string $methodName
     */
    private function __construct(string $handlerName, string $inputChannel, string $objectToInvokeReference, string $methodName)
    {
        $this->handlerName = $handlerName;
        $this->inputMessageChannelName = $inputChannel;
        $this->objectToInvokeReference = $objectToInvokeReference;
        $this->methodName = $methodName;
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @return RouterBuilder
     */
    public static function create(string $handlerName, string $inputChannelName, string $objectToInvokeReference, string $methodName) : self
    {
        return new self($handlerName, $inputChannelName, $objectToInvokeReference, $methodName);
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param array $typeToChannelMapping
     * @return RouterBuilder
     */
    public static function createPayloadTypeRouter(string $handlerName, string $inputChannelName, array $typeToChannelMapping) : self
    {
        $routerBuilder = self::create($handlerName, $inputChannelName, "", 'route');
        $routerBuilder->setObjectToInvoke(PayloadTypeRouter::create($typeToChannelMapping));

        return $routerBuilder;
    }

    /**
     * @param string $handlerName
     * @param string $inputChannelName
     * @param string $headerName
     * @param array $headerValueToChannelMapping
     * @return RouterBuilder
     */
    public static function createHeaderValueRouter(string $handlerName, string $inputChannelName, string $headerName, array $headerValueToChannelMapping) : self
    {
        $routerBuilder = self::create($handlerName, $inputChannelName, "", 'route');
        $routerBuilder->setObjectToInvoke(HeaderValueRouter::create($headerName, $headerValueToChannelMapping));

        return $routerBuilder;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        $requiredReferenceNames = $this->requiredReferenceNames;
        $requiredReferenceNames[] = $this->objectToInvokeReference;

        return $requiredReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function registerRequiredReference(string $referenceName): void
    {
        $this->requiredReferenceNames[] = $referenceName;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): void
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

        $this->methodParameterConverters = $methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService) : MessageHandler
    {
        $objectToInvoke = $this->objectToInvoke ? $this->objectToInvoke : $referenceSearchService->findByReference($this->objectToInvokeReference);

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverters as $methodParameterConverterBuilder) {
            $methodParameterConverters[] = $methodParameterConverterBuilder->build($referenceSearchService);
        }

        return Router::create(
            $channelResolver,
            $objectToInvoke,
            $this->methodName,
            $this->resolutionRequired,
            $methodParameterConverters
        );
    }

    /**
     * @param bool $resolutionRequired
     * @return RouterBuilder
     */
    public function setResolutionRequired(bool $resolutionRequired) : self
    {
        $this->resolutionRequired = $resolutionRequired;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->handlerName;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputMessageChannelName;
    }

    public function __toString()
    {
        return "router";
    }

    /**
     * @param object $objectToInvoke
     */
    private function setObjectToInvoke($objectToInvoke) : void
    {
        Assert::isObject($objectToInvoke, "Object to invoke in router must be object");

        $this->objectToInvoke = $objectToInvoke;
    }
}