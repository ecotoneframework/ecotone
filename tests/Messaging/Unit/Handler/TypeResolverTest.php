<?php

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\TypeResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\ParameterAttribute;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\TestingNamespace\Correct\ClassWithPromotedConstructorParameterAttribute;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class TypeResolverTest extends TestCase
{
    public function test_it_can_resolve_promoted_properties(): void
    {
        $typeResolver = TypeResolver::create();
        $reflectionClass = new ReflectionClass(ClassWithPromotedConstructorParameterAttribute::class);
        /** @var InterfaceParameter $firstParameter */
        [$firstParameter] = $typeResolver->getMethodParameters($reflectionClass, '__construct');

        self::assertEquals([new ParameterAttribute()], $firstParameter->getAnnotations());
    }
}
