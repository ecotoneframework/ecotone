<?php

namespace Test\Ecotone\Lite;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Lite\InMemoryPSRContainer;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\CoinGateway;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\MultiplyCoins;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\Shop;

/**
 * Class MessagingConfigurationTest
 * @package Test\Ecotone\Lite
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MessagingConfigurationTest extends TestCase
{
    public function test_registering_with_gateway_aware_container()
    {
        $container = InMemoryPSRContainer::createFromObjects([
            new MultiplyCoins(), new Shop(),
        ]);
        $ecotone = EcotoneLite::bootstrap(
            containerOrAvailableServices: $container,
            namespaces: ["Test\Ecotone\Messaging\Fixture\Behat\Presend"],
            enabledModules: [],
            allowGatewaysToBeRegisteredInContainer: true,
            pathToRootCatalog: __DIR__
        );

        $this->assertInstanceOf(
            CoinGateway::class,
            $container->get(CoinGateway::class)
        );
    }
}
