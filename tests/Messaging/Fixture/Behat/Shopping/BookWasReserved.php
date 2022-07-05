<?php

namespace Ecotone\Tests\Messaging\Fixture\Behat\Shopping;

/**
 * Class BookWasReserved
 * @package Ecotone\Tests\Messaging\Fixture\Behat\Shopping
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BookWasReserved
{
    /**
     * @var string
     */
    private $bookName;

    /**
     * BookWasReserved constructor.
     * @param string $bookName
     */
    public function __construct(string $bookName)
    {
        $this->bookName = $bookName;
    }
}