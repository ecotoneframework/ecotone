<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class DeleteArticleCommand
 * @package Test\Ecotone\Modelling\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CloseArticleCommand
{
    #[TargetAggregateIdentifier("author")]
    private $authorName;
    #[TargetAggregateIdentifier("title")]
    private $titleName;
    #[TargetAggregateIdentifier("additionalUnusedIdentifier")]
    private $additionalUnusedIdentifier;
    #[TargetAggregateIdentifier("isPublished")]
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
    public static function createWith(string $author, string $title) : self
    {
        return new self($author, $title);
    }

}