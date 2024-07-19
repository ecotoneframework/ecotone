<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

/**
 * Class BookWasReserved
 * @package Test\Ecotone\Messaging\Fixture\Behat\Shopping
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
