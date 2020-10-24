<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

/**
 * Class AfterCalculation
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
#[\Attribute]
class AfterMultiplyCalculation
{
    /**
     * @var integer
     */
    public $amount;
}