<?php


namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;


interface IncreaseAmountCommand
{
    /**
     * @return int
     */
    public function getAmount(): int;
}