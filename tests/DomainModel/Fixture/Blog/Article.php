<?php

namespace Test\Ecotone\DomainModel\Fixture\Blog;

use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\AggregateIdentifier;
use Ecotone\DomainModel\Annotation\CommandHandler;

/**
 * Class Article
 * @package Test\Ecotone\DomainModel\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class Article
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $author;
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $title;
    /**
     * @var string
     */
    private $content;

    /**
     * Article constructor.
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
     * @param PublishArticleCommand $command
     * @return Article
     * @CommandHandler()
     */
    public static function createWith(PublishArticleCommand $command) : self
    {
        return new self($command->getAuthor(), $command->getTitle(), $command->getContent());
    }

    /**
     * @param PublishArticleWithTitleOnlyCommand $command
     *
     * @return Article
     * @CommandHandler()
     */
    public static function createWithoutContent(PublishArticleWithTitleOnlyCommand $command) : self
    {
        return new self($command->getAuthor(), $command->getTitle(), "");
    }

    /**
     * @param ChangeArticleContentCommand $command
     * @return bool
     * @CommandHandler()
     */
    public function changeContent(ChangeArticleContentCommand $command) : bool
    {
        $this->content = $command->getContent();

        return true;
    }

    /**
     * @param CloseArticleCommand $command
     * @return void
     */
    public function close(CloseArticleCommand $command) : void
    {

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