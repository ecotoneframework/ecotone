<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\Ecotone\Messaging\BaseEcotoneTestCase;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStateStoredRepository;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\EventSourcing\Comment;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\Article;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\ArticleRepository;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\Author;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\Page;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\PageRepository;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard\RepositoryBusinessInterface;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class CustomRepositoriesTest extends BaseEcotoneTestCase
{
    public function test_using_custom_repository_for_standard_aggregates()
    {
        $articleRepository = ArticleRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, RepositoryBusinessInterface::class, Page::class, Author::class],
            [ArticleRepository::class => $articleRepository],
            enabledModules: [],
            addInMemoryStateStoredRepository: false,
        );

        $this->verify(Article::create('123'), $ecotoneLite, 'create.article', Article::class, $articleRepository);
        $this->assertEquals(
            Article::create('123'),
            $ecotoneLite->getGateway(RepositoryBusinessInterface::class)->getArticle('123')
        );
    }

    public function test_using_two_custom_repositories_for_standard_aggregates()
    {
        $articleRepository = ArticleRepository::createEmpty();
        $pageRepository = PageRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, Page::class, Author::class, PageRepository::class, RepositoryBusinessInterface::class],
            [
                ArticleRepository::class => $articleRepository,
                PageRepository::class => $pageRepository,
            ],
            enabledModules: [],
            addInMemoryStateStoredRepository: false,
        );

        $this->verify(Article::create('123'), $ecotoneLite, 'create.article', Article::class, $articleRepository);
        $this->verify(Page::create('123'), $ecotoneLite, 'create.page', Page::class, $pageRepository);
        $this->assertEquals(
            Article::create('123'),
            $ecotoneLite->getGateway(RepositoryBusinessInterface::class)->getArticle('123')
        );
        $this->assertEquals(
            Page::create('123'),
            $ecotoneLite->getGateway(RepositoryBusinessInterface::class)->getPage('123')
        );
    }

    public function test_default_repository_is_used_when_multiple_repositories_are_registered_for_different_standard_aggregate()
    {
        $articleRepository = ArticleRepository::createEmpty();
        $pageRepository = PageRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, Page::class, PageRepository::class, Author::class, RepositoryBusinessInterface::class],
            [
                ArticleRepository::class => $articleRepository,
                PageRepository::class => $pageRepository,
            ],
            enabledModules: [],
            addInMemoryStateStoredRepository: true,
        );

        $this->verify(Author::create('123'), $ecotoneLite, 'create.author', Author::class, null);
    }

    #[DataProvider('enterpriseMode')]
    public function test_default_repository_is_used_when_multiple_repositories_are_registered_for_different_type(
        bool $isEnterprise
    ) {
        $articleRepository = ArticleRepository::createEmpty();
        $pageRepository = PageRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, Page::class, PageRepository::class, Comment::class],
            [
                ArticleRepository::class => $articleRepository,
                PageRepository::class => $pageRepository,
            ],
            enabledModules: [],
            licenceKey: $isEnterprise ? LicenceTesting::VALID_LICENCE : null,
        );

        // We have provided multiple standard repositories, therefore there is single Event Sourced repository
        $ecotoneLite->sendCommandWithRouting('create.comment', '123');

        $this->assertNotNull($ecotoneLite->getAggregate(Comment::class, '123'));
    }

    private function verify(object $expectedAggregate, FlowTestSupport $ecotoneLite, string $creationMethod, string $className, ?InMemoryStateStoredRepository $customRepository): void
    {
        $this->assertEquals(
            $expectedAggregate,
            $ecotoneLite
                ->sendCommandWithRouting($creationMethod, '123')
                ->getAggregate($className, '123')
        );

        if ($customRepository) {
            $this->assertEquals(
                $expectedAggregate,
                $customRepository->findBy($className, ['id' => '123'])
            );
        }
    }
}
