<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\Presend;

class MultiplyCoins
{
    #[Presend(pointcut: "Test\Ecotone\Messaging\Fixture\Behat\Presend\*")]
    public function addUser(int $payload) : int
    {
        return $payload * 2;
    }
}