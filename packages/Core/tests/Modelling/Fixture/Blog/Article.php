<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class Article
{
    #[AggregateIdentifier]
    private string $author;
    #[AggregateIdentifier]
    private string $title;
    /**
     * @var string
     */
    private $content;
    /**
     * @var bool
     */
    private $isPublished;

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

    #[CommandHandler]
    public static function createWith(PublishArticleCommand $command) : self
    {
        return new self($command->getAuthor(), $command->getTitle(), $command->getContent());
    }

    #[CommandHandler]
    public static function createWithoutContent(PublishArticleWithTitleOnlyCommand $command) : self
    {
        return new self($command->getAuthor(), $command->getTitle(), "");
    }

    #[CommandHandler]
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