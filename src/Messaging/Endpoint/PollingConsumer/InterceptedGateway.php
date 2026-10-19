<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Handler\NonProxyGateway;

/**
 * licence Apache-2.0
 */
class InterceptedGateway implements NonProxyGateway
{
    /**
     * @param ConsumerInterceptor[] $interceptors
     */
    public function __construct(private NonProxyGateway $gateway, private array $interceptors)
    {
    }

    public function execute(array $methodArgumentValues)
    {
        foreach ($this->interceptors as $consumerInterceptor) {
            $consumerInterceptor->postSend();
        }
        return $this->gateway->execute($methodArgumentValues);
    }
}
