<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\StandardRepository;

/**
 * Class InMemoryArticleRepository
 * @package Test\Ecotone\Modelling\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryArticleStandardRepository implements StandardRepository
{
    /**
     * @var Article[]
     */
    private $articles;

    /**
     * InMemoryArticleRepository constructor.
     * @param Article[] $articles
     */
    private function __construct(array $articles)
    {
        foreach ($articles as $article) {
            $this->save([], $article, [], null);
        }
    }

    /**
     * @return InMemoryArticleStandardRepository
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param array $articles
     * @return InMemoryArticleStandardRepository
     */
    public static function createWith(array $articles) : self
    {
        return new self($articles);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers) : ?object
    {
        $identifier = $identifiers['author'] . $identifiers['title'];

        if (!array_key_exists($identifier, $this->articles)) {
            throw AggregateNotFoundException::create("Article with id {$identifier} doesn't exists having: " . implode(",", array_keys($this->articles)));
        }

        return $this->articles[$identifier];
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Article::class;
    }

    /**
     * @param array $identifiers
     * @param Article $aggregate
     * @param array $metadata
     * @param int|null $expectedVersion
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $this->articles[$this->getIdentifier($aggregate)] = $aggregate;
    }

    /**
     * @param $aggregate
     * @return string
     */
    private function getIdentifier(Article $aggregate): string
    {
        return $aggregate->getAuthor() . $aggregate->getTitle();
    }
}