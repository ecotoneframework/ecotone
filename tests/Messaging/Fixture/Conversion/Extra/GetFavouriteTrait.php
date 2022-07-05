<?php


namespace Ecotone\Tests\Messaging\Fixture\Conversion\Extra;

use Ecotone\Tests\Messaging\Fixture\Conversion\Extra\PrivateDetails\GetPrivilegeTrait;

/**
 * Class GetFavouriteTrait
 * @package Fixture\Conversion\Extra
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
trait GetFavouriteTrait
{
    public function getYourVeryBestFavourite(?Favourite $favourite) : ?Favourite
    {

    }

    public function getLessFavourite(Favourite $favourite) : Favourite
    {

    }
}