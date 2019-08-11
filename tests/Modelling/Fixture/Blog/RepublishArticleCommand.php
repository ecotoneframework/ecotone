<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

/**
 * Class RepubishArticleCommand
 * @package Test\Ecotone\Modelling\Fixture\Blog
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RepublishArticleCommand
{
    /**
     * @var string
     */
    private $author;

    /**
     * PublishArticleCommand constructor.
     * @param string $author
     */
    private function __construct(string $author)
    {
        $this->author = $author;
    }

    /**
     * @param string $author
     * @return RepublishArticleCommand
     */
    public static function createWith(string $author) : self
    {
        return new self($author);
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }
}