<?php

namespace Tests\Ecotone\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterface
 * @package Tests\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceSendOnlyWithThreeArguments
{
    public function calculate(int $number, int $multiplyBy, float $percentage) : void;
}