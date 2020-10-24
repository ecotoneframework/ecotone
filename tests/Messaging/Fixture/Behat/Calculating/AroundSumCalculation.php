<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

/**
 * Class AroundSumCalculation
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
#[\Attribute]
class AroundSumCalculation
{
    /**
     * @var integer
     */
    public $amount;
}