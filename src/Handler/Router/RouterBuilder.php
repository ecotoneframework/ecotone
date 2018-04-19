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
     * @var string|null
     */
    private $defaultResolution = null;

    /**
     * RouterBuilder constructor.
     * @param string $inputChannel
     * @param string $objectToInvokeReference
     * @param string $methodName
     */
    private function __construct(string $inputChannel, string $objectToInvokeReference, string $methodName)
    {
        $this->inputMessageChannelName = $inputChannel;
        $this->objectToInvokeReference = $objectToInvokeReference;
        $this->methodName = $methodName;
    }

    /**
     * @param string $inputChannelName
     * @param string $objectToInvokeReference
     * @param string $methodName
     * @return RouterBuilder
     */
    public static function create(string $inputChannelName, string $objectToInvokeReference, string $methodName) : self
    {
        return new self($inputChannelName, $objectToInvokeReference, $methodName);
    }

    /**
     * @param string $inputChannelName
     * @param array $typeToChannelMapping
     * @return RouterBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createPayloadTypeRouter(string $inputChannelName, array $typeToChannelMapping) : self
    {
        $routerBuilder = self::create($inputChannelName, "", 'route');
        $routerBuilder->setObjectToInvoke(PayloadTypeRouter::create($typeToChannelMapping));

        return $routerBuilder;
    }

    /**
     * @param string $inputChannelName
     * @param $customRouterObject
     * @param string $methodName
     * @return RouterBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createRouterFromObject(string $inputChannelName, $customRouterObject, string $methodName) : self
    {
        Assert::isObject($customRouterObject, "Custom router must be object");

        $routerBuilder = self::create($inputChannelName, "", $methodName);
        $routerBuilder->setObjectToInvoke($customRouterObject);

        return $routerBuilder;
    }

    /**
     * @param string $inputChannelName
     *
     * @return RouterBuilder
     */
    public static function createPayloadTypeRouterByClassName(string $inputChannelName) : self
    {
        $routerBuilder = self::create($inputChannelName, "", 'route');
        $routerBuilder->setObjectToInvoke(PayloadTypeRouter::createWithRoutingByClass());

        return $routerBuilder;
    }

    /**
     * @param string $inputChannelName
     * @param string $headerName
     * @param array $headerValueToChannelMapping
     * @return RouterBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createHeaderValueRouter(string $inputChannelName, string $headerName, array $headerValueToChannelMapping) : self
    {
        $routerBuilder = self::create($inputChannelName, "", 'route');
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders) : self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, MessageToParameterConverterBuilder::class);

        $this->methodParameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
    {
        $this->inputMessageChannelName = $inputChannelName;

        return $this;
    }

    /**
     * @param string $channelName
     * @return RouterBuilder
     */
    public function withDefaultResolutionChannel(string $channelName) : self
    {
        $this->defaultResolution = $channelName;

        return $this;
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
            $methodParameterConverters,
            $this->defaultResolution
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function setObjectToInvoke($objectToInvoke) : void
    {
        Assert::isObject($objectToInvoke, "Object to invoke in router must be object");

        $this->objectToInvoke = $objectToInvoke;
    }
}