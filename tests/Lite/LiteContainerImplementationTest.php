<?php

namespace Test\Ecotone\Lite;

use Ecotone\Lite\InMemoryContainerImplementation;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Test\StubLogger;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
/**
 * licence Apache-2.0
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
        $logger = StubLogger::create();
        $externalContainer = InMemoryPSRContainer::createFromAssociativeArray([
            'externallyDefined' => $logger,
        ]);
        $container = self::buildContainerFromDefinitions(['aReference' => new Reference('externallyDefined')], $externalContainer);

        self::assertSame($logger, $container->get('aReference'));
    }
}
