<?php

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\Type;
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
    public function test_new_resolver_reads_changed_use_statements_from_the_same_file(): void
    {
        $fileName = tempnam(sys_get_temp_dir(), 'ecotone-use-statements-');
        $contents = "<?php\nuse DateTime as ImportedType;\nreturn new class { /** @return ImportedType[] */ public function values(): array { return []; } };";
        try {
            file_put_contents($fileName, $contents);
            $reflection = new ReflectionClass(require $fileName);
            self::assertEquals(Type::create('array<DateTime>'), TypeResolver::create()->getReturnType($reflection, 'values'));

            file_put_contents($fileName, str_replace('use DateTime ', 'use DateTimeImmutable ', $contents));
            self::assertEquals(Type::create('array<DateTimeImmutable>'), TypeResolver::create()->getReturnType($reflection, 'values'));

            file_put_contents($fileName, $contents);
            self::assertEquals(Type::create('array<DateTime>'), TypeResolver::create()->getReturnType($reflection, 'values'));
        } finally {
            unlink($fileName);
        }
    }

    public function test_new_resolver_reads_same_size_source_changes_with_unchanged_timestamp(): void
    {
        $fileName = tempnam(sys_get_temp_dir(), 'ecotone-use-statements-');
        $contents = "<?php\nuse DateTime as ImportedType;\nreturn new class { /** @return ImportedType[] */ public function values(): array { return []; } };";
        try {
            file_put_contents($fileName, $contents);
            $timestamp = filemtime($fileName);
            $reflection = new ReflectionClass(require $fileName);
            self::assertEquals(Type::create('array<DateTime>'), TypeResolver::create()->getReturnType($reflection, 'values'));

            $changedContents = str_replace('DateTime', 'stdClass', $contents);
            self::assertSame(strlen($contents), strlen($changedContents));
            file_put_contents($fileName, $changedContents);
            touch($fileName, $timestamp);
            clearstatcache(true, $fileName);
            self::assertSame($timestamp, filemtime($fileName));
            self::assertEquals(Type::create('array<stdClass>'), TypeResolver::create()->getReturnType($reflection, 'values'));
        } finally {
            unlink($fileName);
        }
    }

    public function test_it_can_resolve_promoted_properties(): void
    {
        $typeResolver = TypeResolver::create();
        $reflectionClass = new ReflectionClass(ClassWithPromotedConstructorParameterAttribute::class);
        /** @var InterfaceParameter $firstParameter */
        [$firstParameter] = $typeResolver->getMethodParameters($reflectionClass, '__construct');

        self::assertEquals([new ParameterAttribute()], $firstParameter->getAnnotations());
    }
}
