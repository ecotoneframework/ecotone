<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 01.01.18
 * Time: 21:31
 */

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;


/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\Messaging\Config
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