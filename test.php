<?php

require __DIR__ . "/vendor/autoload.php";

interface OrderingService
{
    public function placeOrder(string $some) : void;
}

$factory = new \ProxyManager\Factory\RemoteObjectFactory(new class implements \ProxyManager\Factory\RemoteObject\AdapterInterface{
    /**
     * @inheritDoc
     */
    public function call(string $wrappedClass, string $method, array $params = [])
    {
        echo "it works!";
    }
});

$orderingService = $factory->createProxy(OrderingService::class);

$orderingService->placeOrder('some');
