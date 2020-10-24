<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

/**
 * Class BeforeCalculation
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
#[\Attribute]
class BeforeMultiplyCalculation
{
    /**
     * @var integer
     */
    public $amount;
}