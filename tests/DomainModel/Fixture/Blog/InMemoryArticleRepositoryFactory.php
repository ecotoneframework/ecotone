<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Blog;
use SimplyCodedSoftware\DomainModel\AggregateRepository;
use SimplyCodedSoftware\DomainModel\AggregateRepositoryFactory;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class InMemoryArticleRepositoryFactory
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryArticleRepositoryFactory implements AggregateRepositoryFactory
{
    /**
     * @var InMemoryArticleRepository
     */
    private $articleRepository;

    /**
     * InMemoryArticleRepositoryFactory constructor.
     * @param array $articles
     */
    private function __construct(array $articles)
    {
        $this->articleRepository = InMemoryArticleRepository::createWith($articles);
    }

    public static function createEmpty()
    {
        return new self([]);
    }

    public static function createWith(array $articles) : self
    {
        return new self($articles);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(ReferenceSearchService $referenceSearchService, string $aggregateClassName): bool
    {
        return $aggregateClassName === Article::class;
    }

    /**
     * @inheritDoc
     */
    public function getFor(ReferenceSearchService $referenceSearchService, string $aggregateClassName): AggregateRepository
    {
        return $this->articleRepository;
    }
}