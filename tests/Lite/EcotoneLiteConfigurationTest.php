<?php


namespace Test\Ecotone\Lite;

use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
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
        $cacheDirectory = "/tmp/" . Uuid::uuid4()->toString();
        $configuration1 = EcotoneLiteConfiguration::createWithDefaults(__DIR__ . "/../../", $cacheDirectory, InMemoryPSRContainer::createEmpty());
        $configuration2 = EcotoneLiteConfiguration::createWithCache(__DIR__ . "/../../", $cacheDirectory, InMemoryPSRContainer::createEmpty(), [], true, true, "prod");

        $this->assertEquals($configuration1, $configuration2);
    }
}