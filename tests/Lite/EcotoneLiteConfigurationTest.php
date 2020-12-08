<?php


namespace Test\Ecotone\Lite;

use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Class EcotoneLiteConfigurationTest
 * @package Test\Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EcotoneLiteConfigurationTest extends TestCase
{
    public function test_creating_with_cache()
    {
        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
                                        ->withCacheDirectoryPath("/tmp/" . Uuid::uuid4()->toString());
        $configuration1 = EcotoneLiteConfiguration::createWithConfiguration(__DIR__ . "/../../", InMemoryPSRContainer::createEmpty(), $applicationConfiguration);
        $configuration2 = EcotoneLiteConfiguration::createWithConfiguration(__DIR__ . "/../../", InMemoryPSRContainer::createEmpty(), $applicationConfiguration);

        $this->assertEquals($configuration1, $configuration2);
    }
}