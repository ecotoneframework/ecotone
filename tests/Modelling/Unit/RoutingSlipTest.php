<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\AuditLog;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\CreateMerchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\CreateMerchantService;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\ExtendedCommandBus;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\Merchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\MerchantSubscriber;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\RegisterUser;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\User;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\Article;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\ArticleRepository;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\ArticleService;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\Author;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\Page;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\RepositoryBusinessInterface;

/**
 * licence Apache-2.0
 * @internal
 */
final class RoutingSlipTest extends TestCase
{
    public function test_using_routing_slip_on_factory_command_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, AuditLog::class],
            [new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $ecotoneLite->sendDirectToChannel(
            RegisterUser::class,
            new RegisterUser('123'),
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );
        $this->assertEquals(['123'], $ecotoneLite->sendQueryWithRouting('audit.getData'));
        $this->assertNotNull($ecotoneLite->getAggregate(User::class, '123'));
    }

    public function test_routing_slip_is_not_propagated_to_next_gateway_invocation(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, MerchantSubscriber::class, AuditLog::class, User::class],
            [new MerchantSubscriber(), new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $ecotoneLite->sendDirectToChannel(
            'create.merchant',
            new CreateMerchant('123'),
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );

        /**
         * As called through Messaging Gateway directly this will use routing slip,
         * however underlying Event Bus should not, that's why we validate containing of '123'
         */
        $this->assertEquals([
            '123',
        ], $ecotoneLite->sendQueryWithRouting('audit.getData'));
    }

    public function test_routing_slip_will_not_be_propagated_by_command_bus(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, MerchantSubscriber::class, AuditLog::class, User::class],
            [new MerchantSubscriber(), new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $ecotoneLite->sendCommand(
            new CreateMerchant('123'),
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );

        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('audit.getData'));
    }

    public function test_routing_slip_will_not_be_propagated_by_extended_command_bus(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, MerchantSubscriber::class, AuditLog::class, User::class, ExtendedCommandBus::class],
            [new MerchantSubscriber(), new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        /** @var ExtendedCommandBus $commandBus */
        $commandBus = $ecotoneLite->getGateway(ExtendedCommandBus::class);
        $commandBus->send(
            new CreateMerchant('123'),
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );

        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('audit.getData'));
    }

    public function test_routing_slip_will_not_be_propagated_by_query_bus(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, MerchantSubscriber::class, AuditLog::class, User::class],
            [new MerchantSubscriber(), new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $ecotoneLite->sendQueryWithRouting(
            'audit.getData',
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );

        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('audit.getData'));
    }

    public function test_routing_slip_will_not_be_propagated_by_business_interface(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [CreateMerchantService::class, Merchant::class, MerchantSubscriber::class, AuditLog::class, User::class],
            [new MerchantSubscriber(), new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        /** @var CreateMerchantService $gateway */
        $gateway = $ecotoneLite->getGateway(CreateMerchantService::class);
        $gateway->create(
            new CreateMerchant('123'),
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );

        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('audit.getData'));
    }

    public function test_routing_slip_will_not_be_propagated_by_event_bus(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [MerchantSubscriber::class, AuditLog::class, User::class],
            [new MerchantSubscriber(), new AuditLog()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $ecotoneLite->publishEvent(
            new RegisterUser('123'),
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );
        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('audit.getData'));
    }

    public function test_routing_slip_will_not_be_propagated_by_business_repository(): void
    {
        $articleRepository = ArticleRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, RepositoryBusinessInterface::class, Page::class, Author::class, AuditLog::class, ArticleService::class],
            [ArticleRepository::class => $articleRepository, new AuditLog(), new ArticleService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            addInMemoryStateStoredRepository: false,
        );

        // First create an article to retrieve
        $ecotoneLite->sendDirectToChannel('create.article', '123');

        // Call command handler that uses business repository internally
        // The routing slip should be called once for the command handler, but not propagated to the repository
        $ecotoneLite->sendDirectToChannel(
            'get.article.via.service',
            '123',
            metadata: [
                MessageHeaders::ROUTING_SLIP => 'audit',
            ]
        );

        // The audit should contain only the result from the command handler (Article object)
        $auditData = $ecotoneLite->sendQueryWithRouting('audit.getData');
        $this->assertCount(1, $auditData);
        $this->assertInstanceOf(Article::class, $auditData[0]);
    }
}
