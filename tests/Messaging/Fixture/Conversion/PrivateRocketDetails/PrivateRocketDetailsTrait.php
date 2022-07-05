<?php


namespace Tests\Ecotone\Messaging\Fixture\Conversion\PrivateRocketDetails;


use Tests\Ecotone\Messaging\Fixture\Conversion\PublicRocketDetails\PublicRocketDetailsTrait;

trait PrivateRocketDetailsTrait
{
    use PublicRocketDetailsTrait;

    private ?PrivateDetails $privateDetails;
}