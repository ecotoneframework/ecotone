<?php declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Router;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class RouterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilder implements MessageHandlerBuilder
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
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var array|MethodArgument[]
     */
    private $methodArguments = [];
    /**
     * @var bool
     */
    private $resolutionRequired = true;

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
    public function build(): MessageHandler
    {
        $objectToInvoke = $this->objectToInvoke ? $this->objectToInvoke : $this->referenceSearchService->findByReference($this->objectToInvokeReference);

        return Router::create(
            $this->channelResolver,
            $objectToInvoke,
            $this->methodName,
            $this->resolutionRequired,
            $this->methodArguments
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

    /**
     * @inheritDoc
     */
    public function setReferenceSearchService(ReferenceSearchService $referenceSearchService): MessageHandlerBuilder
    {
        $this->referenceSearchService = $referenceSearchService;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setChannelResolver(ChannelResolver $channelResolver): MessageHandlerBuilder
    {
        $this->channelResolver = $channelResolver;

        return $this;
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