<?php

namespace Test\Ecotone\Lite;

use Ecotone\Messaging\Config\Container\AttributeReference;
use Ecotone\Messaging\Config\Container\Compiler\RegisterInterfaceToCallReferences;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueConverter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Test\Ecotone\Lite\Fixtures\AnInterfaceWithComplexAttribute;
use Test\Ecotone\Lite\Fixtures\AroundCalculation;
use Test\Ecotone\Lite\Fixtures\Sum;

/**
 * licence Apache-2.0
 */
abstract class ContainerImplementationTestCase extends TestCase
{
    public function test_it_resolves_simple_definitions_and_references(): void
    {
        $container = self::buildContainerFromDefinitions([
            'def1' => new Definition(WithNoDependencies::class),
            'def2' => new Definition(WithAStringDependency::class, ['someName']),
            'def3' => new Definition(WithAReferenceDependency::class, [new Reference('def1')]),
        ]);


        self::assertEquals(new WithNoDependencies(), $container->get('def1'));
        self::assertEquals(new WithAStringDependency('someName'), $container->get('def2'));
        self::assertEquals(new WithAReferenceDependency($container->get('def1')), $container->get('def3'));

        self::assertSame($container->get('def1'), $container->get('def3')->getDependency());
    }

    public function test_it_executes_registered_method_calls(): void
    {
        $aDefinition = (new Definition(WithNoDependencies::class))
            ->addMethodCall('aMethod', ['aParameter'])
            ->addMethodCall('aMethod', [2]);

        $container = self::buildContainerFromDefinitions([
            'aDefinition' => $aDefinition,
        ]);

        self::assertEquals([['aParameter'], [2]], $container->get('aDefinition')->methodCalls);
    }

    public function test_it_can_resolve_defined_objects(): void
    {
        $aDefinedObject = new ADefinedObject('aName', new ADefinedObject('anOtherName', null));

        $container = self::buildContainerFromDefinitions([
            'aDefinition' => $aDefinedObject,
        ]);

        self::assertEquals($aDefinedObject, $container->get('aDefinition'));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function test_it_can_resolve_complex_attributes(): void
    {
        $aValueConverterWithComplexAttribute = new Definition(ValueConverter::class, [
            new AttributeReference(AroundCalculation::class, AnInterfaceWithComplexAttribute::class, 'calculate'),
        ]);
        $container = self::buildContainerFromDefinitions([
            'aValueConverterWithComplexAttribute' => $aValueConverterWithComplexAttribute,
        ]);

        self::assertEquals(
            ValueConverter::createWith(new AroundCalculation(new Sum(3))),
            $container->get('aValueConverterWithComplexAttribute')
        );

    }

    protected static function buildContainerFromDefinitions(array $definitions, ?ContainerInterface $externalContainer = null): ContainerInterface
    {
        $builder = new ContainerBuilder();
        foreach ($definitions as $id => $definition) {
            $builder->replace($id, $definition);
        }
        $builder->addCompilerPass(new RegisterInterfaceToCallReferences());
        return static::getContainerFrom($builder, $externalContainer);
    }

    abstract protected static function getContainerFrom(ContainerBuilder $builder, ?ContainerInterface $externalContainer = null): ContainerInterface;
}

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
class WithNoDependencies
{
    public mixed $methodCalls = [];
    public function aMethod($aParameter): void
    {
        $this->methodCalls[] = [$aParameter];
    }
}

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
class WithAStringDependency
{
    public function __construct(public string $name)
    {
    }
}

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
class WithAReferenceDependency
{
    public function __construct(public WithNoDependencies $withNoDependencies)
    {
    }

    public function getDependency(): WithNoDependencies
    {
        return $this->withNoDependencies;
    }
}

/**
 * licence Apache-2.0
 */
class ADefinedObject implements DefinedObject
{
    public function __construct(public string $name, public ?ADefinedObject $anOtherDefinedObject)
    {
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->name, $this->anOtherDefinedObject]);
    }
}
