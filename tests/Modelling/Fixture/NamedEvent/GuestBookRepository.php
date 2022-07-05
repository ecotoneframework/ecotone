<?php


namespace Ecotone\Tests\Modelling\Fixture\NamedEvent;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Tests\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[Repository]
class GuestBookRepository extends InMemoryStandardRepository
{

}