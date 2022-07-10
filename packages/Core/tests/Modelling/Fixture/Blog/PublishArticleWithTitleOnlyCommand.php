<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

/**
 * Class PublishArticleWithoutAuthorCommand
 * @package Test\Ecotone\Modelling\Fixture\Blog
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PublishArticleWithTitleOnlyCommand
{
    /**
     * @var string
     */
    private $author;
    /**
     * @var string
     */
    private $title;

    /**
     * PublishArticleCommand constructor.
     * @param string $author
     * @param string $title
     */
    private function __construct(string $author, string $title)
    {
        $this->author = $author;
        $this->title = $title;
    }

    /**
     * @param string $author
     * @param string $title
     *
     * @return PublishArticleWithTitleOnlyCommand
     */
    public static function createWith(string $author, string $title) : self
    {
        return new self($author, $title);
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
}