<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnlyWithThreeArguments
{
    public function calculate(int $number, int $multiplyBy, float $percentage) : void;
}