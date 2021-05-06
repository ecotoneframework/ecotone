<?php


namespace Test\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Modelling\Attribute\Repository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[Repository]
class GuestBookRepository extends InMemoryStandardRepository
{

}