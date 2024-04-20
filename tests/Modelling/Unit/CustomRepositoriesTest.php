<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
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
final class CustomRepositoriesTest extends TestCase
{
    public function test_using_custom_repository_for_standard_aggregates()
    {
        $articleRepository = ArticleRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, RepositoryBusinessInterface::class],
            [ArticleRepository::class => $articleRepository],
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
            [Article::class, ArticleRepository::class, Page::class, PageRepository::class, RepositoryBusinessInterface::class],
            [
                ArticleRepository::class => $articleRepository,
                PageRepository::class => $pageRepository,
            ],
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

    public function test_default_repository_are_not_used_when_multiple_repositories_provided()
    {
        $articleRepository = ArticleRepository::createEmpty();
        $pageRepository = PageRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, Page::class, PageRepository::class, Author::class, RepositoryBusinessInterface::class],
            [
                ArticleRepository::class => $articleRepository,
                PageRepository::class => $pageRepository,
            ],
            addInMemoryStateStoredRepository: true,
        );

        // As more than one repository is available, the repository need to be handled explicitly
        $this->expectException(InvalidArgumentException::class);

        $this->verify(Author::create('123'), $ecotoneLite, 'create.author', Author::class, null);
    }

    public function test_default_repository_is_used_when_multiple_repositories_are_registered_for_different_type()
    {
        $articleRepository = ArticleRepository::createEmpty();
        $pageRepository = PageRepository::createEmpty();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Article::class, ArticleRepository::class, Page::class, PageRepository::class, Comment::class],
            [
                ArticleRepository::class => $articleRepository,
                PageRepository::class => $pageRepository,
            ],
        );

        // We have provided multiple standard repositories, therefore there is single Event Sourced repository
        $ecotoneLite->sendCommandWithRoutingKey('create.comment', '123');

        $this->assertNotNull($ecotoneLite->getAggregate(Comment::class, '123'));
    }

    private function verify(object $expectedAggregate, FlowTestSupport $ecotoneLite, string $creationMethod, string $className, ?InMemoryStandardRepository $customRepository): void
    {
        $this->assertEquals(
            $expectedAggregate,
            $ecotoneLite
                ->sendCommandWithRoutingKey($creationMethod, '123')
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
