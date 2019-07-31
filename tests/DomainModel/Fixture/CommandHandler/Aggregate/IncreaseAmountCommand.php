<?php


namespace Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate;


interface IncreaseAmountCommand
{
    /**
     * @return int
     */
    public function getAmount(): int;
}