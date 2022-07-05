<?php

namespace Tests\Ecotone\Messaging\Fixture\Behat\Shopping;

/**
 * Class BookWasReserved
 * @package Tests\Ecotone\Messaging\Fixture\Behat\Shopping
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