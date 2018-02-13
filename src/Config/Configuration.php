<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 01.01.18
 * Time: 21:31
 */

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;


/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Configuration
{
    /**
     * @return ReferenceSearchService
     */
    public function getReferenceSearchService() : ReferenceSearchService;

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): MessagingSystemConfiguration;

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): MessagingSystemConfiguration;

    /**
     * @param ConsumerBuilder $consumerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerConsumer(ConsumerBuilder $consumerBuilder) : MessagingSystemConfiguration;

    /**
     * @param MessageHandlerConsumerBuilderFactory $consumerFactory
     * @return MessagingSystemConfiguration
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilderFactory $consumerFactory) : MessagingSystemConfiguration;

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder) : MessagingSystemConfiguration;
}