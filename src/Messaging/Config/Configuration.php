<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;


/**
 * Class Configuration
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Configuration
{
    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return Configuration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): Configuration;

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     *
     * @return Configuration
     */
    public function registerDefaultChannelFor(MessageChannelBuilder $messageChannelBuilder) : Configuration;

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return Configuration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): Configuration;

    /**
     * @param PollingMetadata $pollingMetadata
     * @return Configuration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): Configuration;

    /**
     * @param ChannelInterceptorBuilder $channelInterceptorBuilder
     * @return Configuration
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder) : Configuration;

    /**
     * @param string $asynchronousChannelName
     * @param string $targetEndpointId
     * @return Configuration
     */
    public function registerAsynchronousEndpoint(string $asynchronousChannelName, string $targetEndpointId) : Configuration;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return Configuration
     */
    public function registerBeforeSendInterceptor(MethodInterceptor $methodInterceptor): Configuration;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return Configuration
     */
    public function registerBeforeMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration;

    /**
     * @param \Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference $aroundInterceptorReference
     * @return Configuration
     */
    public function registerAroundMethodInterceptor(AroundInterceptorReference $aroundInterceptorReference) : Configuration;

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return Configuration
     */
    public function registerAfterMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration;

    /**
     * @param string[] $referenceNames
     * @return Configuration
     */
    public function requireReferences(array $referenceNames): Configuration;

    public function requireConsumer(string $endpointId) : Configuration;

    /**
     * @param ChannelAdapterConsumerBuilder $consumerBuilder
     *
     * @return Configuration
     */
    public function registerConsumer(ChannelAdapterConsumerBuilder $consumerBuilder) : Configuration;

    /**
     * @param MessageHandlerConsumerBuilder $consumerFactory
     * @return Configuration
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilder $consumerFactory) : Configuration;

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @return Configuration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder) : Configuration;

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;

    /**
     * @return string[]
     */
    public function getOptionalReferences(): array;

    /**
     * @param InterfaceToCall[] $relatedInterfaces
     * @return Configuration
     */
    public function registerRelatedInterfaces(array $relatedInterfaces) : Configuration;

    /**
     * @return GatewayProxyBuilder[]
     */
    public function getRegisteredGateways() : array;

    /**
     * @return ConsoleCommandConfiguration[]
     */
    public function getRegisteredConsoleCommands() : array;

    public function registerConsoleCommand(ConsoleCommandConfiguration $consoleCommandConfiguration) : Configuration;

    /**
     * @param Type $interfaceName
     * @return Configuration
     */
    public function registerInternalGateway(Type $interfaceName) : Configuration;

    /**
     * @return bool
     */
    public function isLazyLoaded() : bool;

    /**
     * @param ConverterBuilder $converterBuilder
     * @return Configuration
     */
    public function registerConverter(ConverterBuilder $converterBuilder) : Configuration;

    /**
     * @param string $referenceName
     * @return Configuration
     */
    public function registerMessageConverter(string $referenceName) : Configuration;

    /**
     * @param ReferenceSearchService $externalReferenceSearchService
     * @return ConfiguredMessagingSystem
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $externalReferenceSearchService): ConfiguredMessagingSystem;
}