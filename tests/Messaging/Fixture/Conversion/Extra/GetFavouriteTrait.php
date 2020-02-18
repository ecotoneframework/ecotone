<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion\Extra;

use Test\Ecotone\Messaging\Fixture\Conversion\Extra\PrivateDetails\GetPrivilegeTrait;

/**
 * Class GetFavouriteTrait
 * @package Fixture\Conversion\Extra
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
trait GetFavouriteTrait
{
    /**
     * @param Favourite $favourite
     * @return Favourite
     */
    public function getYourVeryBestFavourite($favourite)
    {

    }

    public function getLessFavourite(Favourite $favourite) : Favourite
    {

    }
}