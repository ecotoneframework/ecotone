<?php

namespace Test\Ecotone\Amqp\Configuration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Amqp\AmqpInboundChannelAdapterBuilder;
use Ecotone\Amqp\Configuration\AmqpConsumerModule;
use Ecotone\Amqp\Configuration\AmqpMessageConsumerConfiguration;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Test\Ecotone\Amqp\Fixture\AmqpConsumer\AmqpConsumerExample;

/**
 * Class AmqpConsumerModuleTest
 * @package Test\Ecotone\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class AmqpConsumerModuleTest extends TestCase
{
    public function test_registering_consumer()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerConsumer(
                    AmqpInboundChannelAdapterBuilder::createWith(
                        'someId',
                        'someQueue',
                        'someId',
                        'amqpConnection'
                    )
                    ->withHeaderMapper('ecotone.*')
                    ->withReceiveTimeout(1)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create('amqpConsumer', 'handle')
                        ->withEndpointId('someId.target')
                        ->withInputChannelName('someId')
                        ->withMethodParameterConverters([
                            PayloadBuilder::create('object'),
                        ])
                ),
            $this->prepareConfiguration(
                [
                    AmqpConsumerExample::class,
                ],
                [
                    AmqpMessageConsumerConfiguration::create('someId', 'someQueue', 'amqpConnection')
                        ->withReceiveTimeoutInMilliseconds(1)
                        ->withHeaderMapper('ecotone.*'),
                ]
            )
        );
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array $classes
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function prepareConfiguration(array $classes, array $extensions): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = AmqpConsumerModule::create(InMemoryAnnotationFinder::createFrom($classes), InterfaceToCallRegistry::createEmpty());

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService,
            InterfaceToCallRegistry::createEmpty()
        );

        return $extendedConfiguration;
    }
}
