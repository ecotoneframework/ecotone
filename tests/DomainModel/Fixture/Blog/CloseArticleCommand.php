<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Blog;

use SimplyCodedSoftware\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class DeleteArticleCommand
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Blog
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