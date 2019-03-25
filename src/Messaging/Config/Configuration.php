<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;


/**
 * Class Configuration
 * @package SimplyCodedSoftware\Messaging\Config
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
     * @param MethodInterceptor $methodInterceptor
     * @return Configuration
     */
    public function registerBeforeMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration;

    /**
     * @param \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference $aroundInterceptorReference
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
    public function getRegisteredGateways() : array;

    /**
     * @param ConverterBuilder $converterBuilder
     * @return Configuration
     */
    public function registerConverter(ConverterBuilder $converterBuilder) : Configuration;

    /**
     * @param ReferenceSearchService $externalReferenceSearchService
     * @return ConfiguredMessagingSystem
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $externalReferenceSearchService): ConfiguredMessagingSystem;
}