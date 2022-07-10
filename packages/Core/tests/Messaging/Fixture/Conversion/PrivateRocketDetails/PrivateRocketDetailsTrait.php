<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion\PrivateRocketDetails;


use Test\Ecotone\Messaging\Fixture\Conversion\PublicRocketDetails\PublicRocketDetailsTrait;

trait PrivateRocketDetailsTrait
{
    use PublicRocketDetailsTrait;

    private ?PrivateDetails $privateDetails;
}