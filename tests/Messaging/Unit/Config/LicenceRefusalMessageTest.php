<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\LicensingException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class LicenceRefusalMessageTest extends TestCase
{
    public function test_refusing_an_enterprise_feature_names_where_to_get_a_key_and_how_to_pass_it_on_every_integration(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                configuration: ServiceConfiguration::createWithDefaults()
                    ->withServiceName('licence_refusal')
                    ->withExtensionObjects([DistributedServiceMap::initialize()])
                    ->withEnabledModulePackageNames([]),
            );
            $this->fail('An Enterprise feature was accepted without a licence key');
        } catch (LicensingException $exception) {
            $this->assertStringContainsString('https://ecotone.tech/pricing', $exception->getMessage());
            $this->assertStringContainsString('ServiceConfiguration::withLicenceKey()', $exception->getMessage());
            $this->assertStringContainsString('licenceKey: argument of EcotoneLite::bootstrap()', $exception->getMessage());
            $this->assertStringContainsString("'licenceKey' in config/ecotone.php", $exception->getMessage());
            $this->assertStringContainsString('ecotone.licenceKey', $exception->getMessage());
            $this->assertStringContainsString('licenceKey argument of EcotoneConfig', $exception->getMessage());
        }
    }
}
