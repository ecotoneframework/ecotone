<?php


namespace Tests\Ecotone\Modelling\Fixture\NamedEvent;

use Ecotone\Modelling\Attribute\Repository;
use Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[Repository]
class GuestBookRepository extends InMemoryStandardRepository
{

}