<?php

namespace Ecotone\Laravel;

use Ecotone\Messaging\Config\MessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Illuminate\Contracts\Foundation\Application;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;

/**
 * Class ProxyGenerator
 * @package App\MessagingBundle\DependencyInjection
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyGenerator
{
    public static function createFor(string $referenceName, Application $application, string $interface, string $cacheDirectoryPath): object
    {
        $proxyFactory = ProxyFactory::createWithCache($cacheDirectoryPath);
        $factory = new RemoteObjectFactory(new class ($application, $referenceName) implements AdapterInterface {
            /**
             * @var string
             */
            private $referenceName;
            /**
             * @var Application
             */
            private $application;

            public function __construct(Application $application, string $referenceName)
            {
                $this->referenceName = $referenceName;
                $this->application = $application;
            }

            /**
             * @inheritDoc
             */
            public function call(string $wrappedClass, string $method, array $params = [])
            {
                /** @var MessagingSystem $messagingSystem */
                $messagingSystem = $this->application->get(EcotoneProvider::MESSAGING_SYSTEM_REFERENCE);

                $nonProxyCombinedGateway = $messagingSystem->getNonProxyGatewayByName($this->referenceName);

                return $nonProxyCombinedGateway->executeMethod($method, $params);
            }
        }, $proxyFactory->getConfiguration());

        return $factory->createProxy($interface);
    }
}
