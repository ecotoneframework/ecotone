<?php

namespace Test\Ecotone\Benchmark;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\CreateMerchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\Merchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\MerchantSubscriber;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\User;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

/**
 * @internal
 */
class EcotoneBenchmark extends TestCase
{
    /**
     * @Revs(10)
     * @Iterations(5)
     * @Warmup(1)
     */
    public function bench_running_ecotone_lite()
    {
        $this->execute(
            ServiceConfiguration::createWithDefaults()
                ->withFailFast(false)
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            false
        );
    }

    /**
     * @Revs(10)
     * @Iterations(5)
     * @Warmup(1)
     */
    public function bench_running_ecotone_lite_with_fail_fast()
    {
        $this->execute(
            ServiceConfiguration::createWithDefaults()
                ->withFailFast(true)
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            false
        );
    }

    /**
     * @Revs(10)
     * @Iterations(5)
     * @Warmup(1)
     */
    public function bench_running_ecotone_lite_with_cache()
    {
        $this->execute(
            ServiceConfiguration::createWithDefaults()
                ->withFailFast(false)
                ->withCacheDirectoryPath(sys_get_temp_dir())
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            true
        );
    }

    private function execute(ServiceConfiguration $serviceConfiguration, bool $useCachedVersion): void
    {
        $ecotoneApplication = EcotoneLite::bootstrap(
            [Merchant::class, User::class, MerchantSubscriber::class, InMemoryStandardRepository::class],
            [
                new MerchantSubscriber(), InMemoryStandardRepository::createEmpty(),
            ],
            $serviceConfiguration,
            useCachedVersion: $useCachedVersion,
            allowGatewaysToBeRegisteredInContainer: true
        );

        $merchantId = '123';
        $ecotoneApplication->getCommandBus()->send(new CreateMerchant($merchantId));

        $this->assertTrue(
            $ecotoneApplication->getQueryBus()->sendWithRouting('user.get', metadata: ['aggregate.id' => $merchantId])
        );
    }
}
