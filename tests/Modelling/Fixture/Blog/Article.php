<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class Article
{
    #[Identifier]
    private string $author;
    #[Identifier]
    private ?string $title;
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
     * @param string|null $title
     * @param string $content
     */
    private function __construct(string $author, ?string $title, string $content)
    {
        $this->author = $author;
        $this->title = $title;
        $this->content = $content;
    }

    #[CommandHandler]
    public static function createWith(PublishArticleCommand $command): self
    {
        return new self($command->getAuthor(), $command->getTitle(), $command->getContent());
    }

    #[CommandHandler]
    public static function createWithoutContent(PublishArticleWithTitleOnlyCommand $command): self
    {
        return new self($command->getAuthor(), $command->getTitle(), '');
    }

    #[CommandHandler]
    public static function createWithoutIdentifiers(CreateArticleWithoutIdentifiersCommand $command): self
    {
        return new self('generated-author', 'generated-title', $command->getContent());
    }

    #[CommandHandler]
    public static function createWithSingleIdentifier(CreateArticleWithSingleIdentifierCommand $command): self
    {
        return new self($command->getAuthor(), 'generated-title', $command->getContent());
    }

    #[CommandHandler]
    public static function createWithNullIdentifier(CreateArticleWithNullIdentifierCommand $command): self
    {
        return new self($command->getAuthor(), $command->getTitle(), $command->getContent());
    }

    #[CommandHandler]
    public static function createWithFactoryAssignedNull(CreateArticleWithFactoryAssignedNullCommand $command): self
    {
        // Factory method internally assigns null to title, regardless of command content
        return new self($command->getAuthor(), null, $command->getContent());
    }

    #[CommandHandler]
    public function changeContent(ChangeArticleContentCommand $command): bool
    {
        $this->content = $command->getContent();

        return true;
    }

    /**
     * @param CloseArticleCommand $command
     * @return void
     */
    public function close(CloseArticleCommand $command): void
    {
    }

    #[CommandHandler(routingKey: 'close')]
    public function closeArticle(): void
    {
        $this->isPublished = false;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
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

    public function isPublished(): bool
    {
        return $this->isPublished;
    }
}
