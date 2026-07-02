<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Config\Container\Compiler\RegisterInterfaceToCallReferences;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\SymfonyContainer\EcotoneSymfonyContainerFactory;
use Ecotone\Test\StubLogger;
use Psr\Container\ContainerInterface;
use Test\Ecotone\Lite\ADefinedObject;
use Test\Ecotone\Lite\ContainerImplementationTestCase;
use Test\Ecotone\Lite\WithNoDependencies;

/**
 * licence Apache-2.0
 * @internal
 */
class SymfonyContainerImplementationTest extends ContainerImplementationTestCase
{
    protected static function getContainerFrom(ContainerBuilder $builder, ?ContainerInterface $externalContainer = null): ContainerInterface
    {
        return EcotoneSymfonyContainerFactory::build($builder, ServiceCacheConfiguration::noCache(), $externalContainer);
    }

    public function test_it_resolves_service_ids_containing_anonymous_class_names(): void
    {
        $anonymousService = new class () {
        };
        $serviceId = 'channel-' . get_class($anonymousService) . '.will_load';

        $container = self::buildContainerFromDefinitions([
            $serviceId => new Definition(get_class($anonymousService)),
        ]);

        self::assertEquals($anonymousService, $container->get($serviceId));
    }

    public function test_it_does_not_report_unknown_external_references_as_available(): void
    {
        $container = self::buildContainerFromDefinitions([
            'def1' => new Definition(WithReferenceToUnknown::class, [new Reference('unknownService', ContainerImplementation::NULL_ON_INVALID_REFERENCE)]),
            'def2' => new Definition(WithReferenceToUnknown::class, [new Reference('anotherUnknownService')]),
        ]);

        self::assertNull($container->get('def1')->dependency);
        self::assertFalse($container->has('unknownService'));
        self::assertFalse($container->has('anotherUnknownService'));
    }

    public function test_it_preserves_identity_of_registered_defined_object_instances(): void
    {
        $definedObjectInstance = new ADefinedObject('aName', null);
        $builder = new ContainerBuilder();
        $builder->replace('aService', $definedObjectInstance);
        $builder->addCompilerPass(new RegisterInterfaceToCallReferences());

        $container = static::getContainerFrom($builder);

        self::assertSame($definedObjectInstance, $container->get('aService'));
    }

    public function test_it_preserves_identity_of_nested_defined_object_instances(): void
    {
        $definedObjectInstance = new ADefinedObject('aName', null);
        $container = self::buildContainerFromDefinitions([
            'aService' => new Definition(WithReferenceToUnknown::class, [new DefinedObjectWrapper($definedObjectInstance)]),
        ]);

        self::assertSame($definedObjectInstance, $container->get('aService')->dependency);
    }

    public function test_it_resolves_external_references_into_single_shared_instance(): void
    {
        $externalContainer = new class () implements ContainerInterface {
            public function get(string $id): mixed
            {
                return new WithNoDependencies();
            }

            public function has(string $id): bool
            {
                return $id === 'externallyDefined';
            }
        };

        $container = self::buildContainerFromDefinitions([
            'def1' => new Definition(WithReferenceToUnknown::class, [new Reference('externallyDefined')]),
            'def2' => new Definition(WithReferenceToUnknown::class, [new Reference('externallyDefined')]),
        ], $externalContainer);

        self::assertSame($container->get('def1')->dependency, $container->get('def2')->dependency);
    }

    public function test_it_exposes_defined_service_ids_without_internal_ids(): void
    {
        $container = self::buildContainerFromDefinitions([
            'def1' => new Definition(WithNoDependencies::class),
            'def2' => new Definition(WithReferenceToUnknown::class, [new Reference('externalService', ContainerImplementation::NULL_ON_INVALID_REFERENCE)]),
            'def3' => new Definition(WithReferenceToUnknown::class, [new Reference('anotherExternalService')]),
        ]);

        $definedServiceIds = $container->getDefinedServiceIds();

        self::assertContains('def1', $definedServiceIds);
        self::assertContains('def2', $definedServiceIds);
        self::assertNotContains('service_container', $definedServiceIds);
        self::assertNotContains('ecotone.external_container', $definedServiceIds);
        self::assertNotContains(ContainerInterface::class, $definedServiceIds);
        foreach ($definedServiceIds as $serviceId) {
            self::assertStringNotContainsString('.ecotone.external', $serviceId);
        }
    }

    public function test_it_resolves_references_from_external_container(): void
    {
        $logger = StubLogger::create();
        $externalContainer = InMemoryPSRContainer::createFromAssociativeArray([
            'externallyDefined' => $logger,
        ]);
        $container = self::buildContainerFromDefinitions(['aReference' => new Reference('externallyDefined')], $externalContainer);

        self::assertSame($logger, $container->get('aReference'));
    }
}

/**
 * licence Apache-2.0
 */
class WithReferenceToUnknown
{
    public function __construct(public mixed $dependency)
    {
    }
}
