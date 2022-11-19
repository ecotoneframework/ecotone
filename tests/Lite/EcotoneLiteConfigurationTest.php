<?php

namespace Test\Ecotone\Lite;

use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\MessageEndpoint\Gateway\FileSystem\GatewayWithReplyChannelExample;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\CoinGateway;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\MultiplyCoins;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\Shop;

/**
 * Class EcotoneLiteConfigurationTest
 * @package Test\Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class EcotoneLiteConfigurationTest extends TestCase
{
    public function test_creating_with_cache()
    {
        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
                                        ->withCacheDirectoryPath('/tmp/' . Uuid::uuid4()->toString())
                                        ->withSkippedModulePackageNames([ModulePackageList::AMQP_PACKAGE, ModulePackageList::DBAL_PACKAGE, ModulePackageList::JMS_CONVERTER_PACKAGE, ModulePackageList::EVENT_SOURCING_PACKAGE]);
        $configuration1 = EcotoneLiteConfiguration::createWithConfiguration(__DIR__ . '/../../', InMemoryPSRContainer::createEmpty(), $applicationConfiguration, [], false, [GatewayWithReplyChannelExample::class]);
        $configuration2 = EcotoneLiteConfiguration::createWithConfiguration(__DIR__ . '/../../', InMemoryPSRContainer::createEmpty(), $applicationConfiguration, [], true, [GatewayWithReplyChannelExample::class]);

        $this->assertEquals($configuration1, $configuration2);
    }

    public function test_registering_with_gateway_aware_container()
    {
        $container = InMemoryPSRContainer::createFromObjects([
            new MultiplyCoins(), new Shop(),
        ]);
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
                                ->withNamespaces(["Test\Ecotone\Messaging\Fixture\Behat\Presend"])
                                ->withSkippedModulePackageNames([ModulePackageList::AMQP_PACKAGE, ModulePackageList::DBAL_PACKAGE, ModulePackageList::JMS_CONVERTER_PACKAGE, ModulePackageList::EVENT_SOURCING_PACKAGE]);

        $configuration = EcotoneLiteConfiguration::createWithConfiguration(__DIR__ . '/../../', $container, $serviceConfiguration, [], false);

        $this->assertEquals(
            $configuration->getGatewayByName(CoinGateway::class),
            $container->get(CoinGateway::class)
        );
    }
}
