<?php

namespace Test\Ecotone\DomainModel\Fixture\Blog;
use Ecotone\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class PublishArticleCommand
 * @package Test\Ecotone\DomainModel\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PublishArticleCommand
{
    /**
     * @var string
     * @TargetAggregateIdentifier()
     */
    private $author;
    /**
     * @var string
     * @TargetAggregateIdentifier()
     */
    private $title;
    /**
     * @var string
     */
    private $content;

    /**
     * PublishArticleCommand constructor.
     * @param string $author
     * @param string $title
     * @param string $content
     */
    private function __construct(string $author, string $title, string $content)
    {
        $this->author = $author;
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * @param string $author
     * @param string $title
     * @param string $content
     * @return PublishArticleCommand
     */
    public static function createWith(string $author, string $title, string $content) : self
    {
        return new self($author, $title, $content);
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
}