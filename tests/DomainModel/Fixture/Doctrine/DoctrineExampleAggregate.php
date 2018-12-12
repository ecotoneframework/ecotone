<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class DoctrineExampleAggregate
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Doctrine
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ORM\Entity()
 */
class DoctrineExampleAggregate
{
    /**
     * @var string
     * @ORM\Id()
     */
    private $aggregateId;
}