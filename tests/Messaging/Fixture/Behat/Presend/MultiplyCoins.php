<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\Interceptor\Presend;

class MultiplyCoins
{
    #[Presend(pointcut: "Test\Ecotone\Messaging\Fixture\Behat\Presend\*")]
    public function addUser(int $payload) : int
    {
        return $payload * 2;
    }
}