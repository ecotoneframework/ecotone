<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Modelling\Annotation\TargetAggregateIdentifier;

/**
 * Class DeleteArticleCommand
 * @package Test\Ecotone\Modelling\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CloseArticleCommand
{
    /**
     * @var string
     * @TargetAggregateIdentifier("author")
     */
    private $authorName;
    /**
     * @var string
     * @TargetAggregateIdentifier(identifierName="title")
     */
    private $titleName;
    /**
     * for testing purposes
     *
     * @var string
     * @TargetAggregateIdentifier(identifierName="additionalUnusedIdentifier")
     */
    private $additionalUnusedIdentifier;
    /**
     * for testing purposes
     *
     * @var bool
     * @TargetAggregateIdentifier(identifierName="isPublished")
     */
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