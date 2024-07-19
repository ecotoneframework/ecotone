<?php

namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

/**
 * licence Apache-2.0
 */
class RegisterBook
{
    private string $bookId;

    public function __construct(string $bookId)
    {
        $this->bookId = $bookId;
    }

    public function getBookId(): string
    {
        return $this->bookId;
    }
}
