<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Blog;
use SimplyCodedSoftware\DomainModel\AggregateNotFoundException;
use SimplyCodedSoftware\DomainModel\AggregateRepository;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class InMemoryArticleRepository
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryArticleRepository implements AggregateRepository
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
            $this->save(MessageBuilder::withPayload("some")->build(), $article);
        }
    }

    /**
     * @return InMemoryArticleRepository
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param array $articles
     * @return InMemoryArticleRepository
     */
    public static function createWith(array $articles) : self
    {
        return new self($articles);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers)
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
    public function findWithLockingBy(string $aggregateClassName, array $identifiers, int $expectedVersion)
    {
        return $this->findBy($identifiers);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Article::class;
    }

    /**
     * @param Message $requestMessage
     * @param Article $aggregate
     */
    public function save(Message $requestMessage, $aggregate): void
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