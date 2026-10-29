<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\AggregateNotFoundException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Modelling\Fixture\AggregateWithGateway\Bucket;
use Test\Ecotone\Modelling\Fixture\AggregateWithGateway\BucketGateway;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class AggregateCallingWithGatewayTest extends TestCase
{
    public function test_aggregate_with_gateway(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [Bucket::class, BucketGateway::class],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $bucketId = Uuid::uuid4();

        $ecotone->sendCommandWithRoutingKey(Bucket::CREATE, $bucketId);

        $gateway = $ecotone->getGateway(BucketGateway::class);

        $uuid = Uuid::uuid4();
        $gateway->add(
            $bucketId,
            [
                $uuid->toString() => 'foo',
                Uuid::uuid4()->toString() => 'bar',
            ]
        );

        self::assertEquals('foo', $gateway->get($bucketId, $uuid));
    }

    public function test_throwing_exception_when_aggregate_id_not_found(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [Bucket::class, BucketGateway::class],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $gateway = $ecotone->getGateway(BucketGateway::class);

        $this->expectException(AggregateNotFoundException::class);

        $gateway->getWithoutAggregateIdentifier(Uuid::uuid4());
    }
}
