<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

/**
 * Interface VersionAggregate
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface VersionAggregate
{
    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return int
     */
    public function getVersion() : ?int;
}