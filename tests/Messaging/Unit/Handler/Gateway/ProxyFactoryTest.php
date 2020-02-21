<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\GatewayExecuteClass;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\StringReturningGateway;

class ProxyFactoryTest extends TestCase
{
    public function test_creating_no_cache_proxy()
    {
        $proxyFactory = ProxyFactory::createNoCache();
        $data = "someReply";
        $proxyFactory = unserialize(serialize($proxyFactory));

        /** @var StringReturningGateway $proxy */
        $proxy = $proxyFactory->createProxyClass(StringReturningGateway::class, GatewayExecuteClass::createBuildClosure($data));

        $this->assertEquals($data, $proxy->executeNoParams());
    }

    public function test_creating_with_cache_proxy_with_warmup()
    {
        $cacheDirectoryPath = "/tmp/" . Uuid::uuid4()->toString();
        if (!is_dir($cacheDirectoryPath)) {
            mkdir($cacheDirectoryPath);
        }
        $proxyFactory       = ProxyFactory::createWithCache($cacheDirectoryPath);
        $data               = "someReply";
        $proxyFactory->warmUpCacheFor([StringReturningGateway::class]);

        $proxyFactory = unserialize(serialize($proxyFactory));

        /** @var StringReturningGateway $proxy */
        $proxy = $proxyFactory->createProxyClass(StringReturningGateway::class, GatewayExecuteClass::createBuildClosure($data));

        $this->assertEquals($data, $proxy->executeNoParams());
    }
}