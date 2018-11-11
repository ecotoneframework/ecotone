<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;


/**
 * Class Configuration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
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
     * @param MessageHandlerBuilderWithOutputChannel $methodInterceptor
     * @param int $orderWeight
     * @return Configuration
     */
    public function registerPreCallMethodInterceptor(MessageHandlerBuilderWithOutputChannel $methodInterceptor, int $orderWeight): Configuration;

    /**
     * @param MessageHandlerBuilderWithOutputChannel $methodInterceptor
     * @param int $orderWeight
     * @return Configuration
     */
    public function registerPostCallMethodInterceptor(MessageHandlerBuilderWithOutputChannel $methodInterceptor, int $orderWeight): Configuration;

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
}