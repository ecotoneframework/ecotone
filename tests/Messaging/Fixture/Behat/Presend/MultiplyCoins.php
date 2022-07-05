<?php
declare(strict_types=1);

namespace Ecotone\Tests\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\Interceptor\Presend;

class MultiplyCoins
{
    #[Presend(pointcut: "Ecotone\Tests\Messaging\Fixture\Behat\Presend\*")]
    public function addUser(int $payload) : int
    {
        return $payload * 2;
    }
}