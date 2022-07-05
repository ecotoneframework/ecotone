<?php
declare(strict_types=1);


namespace Ecotone\Tests\Messaging\Fixture\Behat\InterceptedGateway;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\QueryBus;

class SomeQueryHandler
{
    const CALCULATE = "calculate";

    #[QueryHandler(SomeQueryHandler::CALCULATE)]
    public function calculate(int $sum) : int
    {
        return $sum;
    }
}