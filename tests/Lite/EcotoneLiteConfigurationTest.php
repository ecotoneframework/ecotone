<?php

namespace Test\Ecotone\Lite;

use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
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
        $cacheDirectory = '/tmp/' . Uuid::uuid4()->toString();
        $this->assertEquals(
            $this->getConfiguration($cacheDirectory, false),
            $this->getConfiguration($cacheDirectory, true)
        );
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

    private function getConfiguration(string $cacheDirectory, bool $useCachedVersion): Configuration
    {
        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
            ->withCacheDirectoryPath($cacheDirectory)
            ->withSkippedModulePackageNames([ModulePackageList::AMQP_PACKAGE, ModulePackageList::DBAL_PACKAGE, ModulePackageList::JMS_CONVERTER_PACKAGE, ModulePackageList::EVENT_SOURCING_PACKAGE]);

        return MessagingSystemConfiguration::prepare(
            realpath('/tmp/' . Uuid::uuid4()->toString()),
            InMemoryConfigurationVariableService::createEmpty(),
            $applicationConfiguration,
            $useCachedVersion,
            [GatewayWithReplyChannelExample::class]
        );
    }
}
