<?php


namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;


interface IncreaseAmountCommand
{
    /**
     * @return int
     */
    public function getAmount(): int;
}