<?php

namespace Test\SimplyCodedSoftware\Amqp;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Amqp\AmqpAdmin;
use SimplyCodedSoftware\Amqp\AmqpBackedMessageChannelBuilder;
use SimplyCodedSoftware\Amqp\AmqpBinding;
use SimplyCodedSoftware\Amqp\AmqpExchange;
use SimplyCodedSoftware\Amqp\AmqpQueue;
use SimplyCodedSoftware\Amqp\Configuration\AmqpModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class AmqpModuleTest
 * @package Test\SimplyCodedSoftware\Amqp
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpModuleTest extends TestCase
{
    public function test_registering_amqp_backed_message_channel()
    {
        $this->assertEquals(
            AmqpAdmin::createWith([], [AmqpQueue::createWith("some")], []),
            $this->prepareConfigurationAndRetrieveAmqpAdmin(
                [
                    AmqpBackedMessageChannelBuilder::create("some", "amqpConnection")
                ]
            )
        );
    }

    public function test_registering_amqp_inbound_message_channel()
    {
        $amqpExchange = AmqpExchange::createDirectExchange("exchange");
        $amqpQueue = AmqpQueue::createWith("queue");
        $amqpBinding = AmqpBinding::createFromNames("exchange", "queue", "route");

        $this->assertEquals(
            AmqpAdmin::createWith([$amqpExchange], [$amqpQueue], [$amqpBinding]),
            $this->prepareConfigurationAndRetrieveAmqpAdmin([$amqpExchange, $amqpQueue, $amqpBinding])
        );
    }

    /**
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @param array                         $extensions
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     */
    private function prepareConfigurationAndRetrieveAmqpAdmin(array $extensions): AmqpAdmin
    {
        $cqrsMessagingModule = AmqpModule::create(InMemoryAnnotationRegistrationService::createEmpty());

        $extendedConfiguration        = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService
        );

        return $moduleReferenceSearchService->retrieveRequired(AmqpAdmin::REFERENCE_NAME);
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     */
    private function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }
}