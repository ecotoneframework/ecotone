<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Api\Attribute\TargetIdentifier;

/**
 * Class DeleteArticleCommand
 * @package Test\Ecotone\Modelling\Fixture\Blog
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class CloseArticleCommand
{
    #[TargetIdentifier('author')]
    private $authorName;
    #[TargetIdentifier('title')]
    private $titleName;
    #[TargetIdentifier('additionalUnusedIdentifier')]
    private $additionalUnusedIdentifier;
    #[TargetIdentifier('isPublished')]
    private $isPublished;

    /**
     * PublishArticleCommand constructor.
     * @param string $author
     * @param string $title
     */
    private function __construct(string $author, string $title)
    {
        $this->authorName = $author;
        $this->titleName = $title;
    }

    /**
     * @param string $author
     * @param string $title
     * @return self
     */
    public static function createWith(string $author, string $title): self
    {
        return new self($author, $title);
    }
}
