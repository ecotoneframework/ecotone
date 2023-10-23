<?php

namespace Test\Ecotone\Lite;

use Ecotone\Lite\InMemoryContainerImplementation;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Psr\Container\ContainerInterface;
use Test\Ecotone\Messaging\Unit\Handler\Logger\LoggerExample;

/**
 * @internal
 */
class LiteContainerImplementationTest extends ContainerImplementationTestCase
{
    protected static function getContainerFrom(ContainerBuilder $builder, ?ContainerInterface $externalContainer = null): ContainerInterface
    {
        $container = InMemoryPSRContainer::createEmpty();
        $builder->addCompilerPass(new InMemoryContainerImplementation($container, $externalContainer));
        $builder->compile();
        return $container;
    }

    public function test_it_replace_provided_dependencies(): void
    {
        $logger = LoggerExample::create();
        $externalContainer = InMemoryPSRContainer::createFromAssociativeArray([
            'logger' => $logger,
        ]);
        $container = self::buildContainerFromDefinitions(['aReference' => new Reference('logger')], $externalContainer);

        self::assertSame($logger, $container->get('aReference'));
    }
}
