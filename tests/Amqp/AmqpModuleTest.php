<?php

namespace Test\Ecotone\Amqp;

use PHPUnit\Framework\TestCase;
use Ecotone\Amqp\AmqpAdmin;
use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpBinding;
use Ecotone\Amqp\AmqpExchange;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\MessagingException;

/**
 * Class AmqpModuleTest
 * @package Test\Ecotone\Amqp
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