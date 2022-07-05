<?php


namespace Ecotone\Tests\Messaging\Fixture\Conversion\PrivateRocketDetails;


use Ecotone\Tests\Messaging\Fixture\Conversion\PublicRocketDetails\PublicRocketDetailsTrait;

trait PrivateRocketDetailsTrait
{
    use PublicRocketDetailsTrait;

    private ?PrivateDetails $privateDetails;
}