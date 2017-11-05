<?php

namespace Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnlyWithThreeArguments
{
    public function calculate(int $number, int $multiplyBy, float $percentage) : void;
}