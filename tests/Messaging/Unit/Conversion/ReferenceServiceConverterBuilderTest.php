<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Converter;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleIncorrectConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleIncorrectUnionReturnTypeConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleIncorrectUnionSourceTypeConverterService;

/**
 * licence Apache-2.0
 *
 * @internal
 */
class ReferenceServiceConverterBuilderTest extends TestCase
{
    public function test_converting_using_reference_service()
    {
        $handler = new StdClassArrayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ExampleConverterService::class, StdClassArrayHandler::class],
            ['exampleConverterService' => new ExampleConverterService(), StdClassArrayHandler::class => $handler],
            enabledModules: [],
        );

        $ecotone->sendDirectToChannel(StdClassArrayHandler::CHANNEL, ['some']);

        $this->assertEquals([new stdClass()], $handler->received);
    }

    public function test_throwing_exception_if_there_is_more_parameters_than_one_in_converter_reference()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [ExampleIncorrectConverterService::class, StdClassArrayHandler::class],
            [new ExampleIncorrectConverterService(), new StdClassArrayHandler()],
            enabledModules: [],
        );
    }

    public function test_not_throwing_exception_if_converter_containing_source_union_source_type()
    {
        EcotoneLite::bootstrapFlowTesting(
            [ExampleIncorrectUnionSourceTypeConverterService::class, StdClassArrayHandler::class],
            [new ExampleIncorrectUnionSourceTypeConverterService(), new StdClassArrayHandler()],
            enabledModules: [],
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_throwing_exception_if_converter_returning_union_source_type()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [ExampleIncorrectUnionReturnTypeConverterService::class, StdClassArrayHandler::class],
            [new ExampleIncorrectUnionReturnTypeConverterService(), new StdClassArrayHandler()],
            enabledModules: [],
        );
    }

    public function test_static_converter(): void
    {
        $staticConverter = new class () {
            /**
             * @param string[] $data
             * @return stdClass[]
             */
            #[Converter]
            public static function convert(array $data): iterable
            {
                $converted = [];
                foreach ($data as $str) {
                    $converted[] = new stdClass();
                }

                return $converted;
            }
        };

        $handler = new StdClassArrayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$staticConverter::class, StdClassArrayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->sendDirectToChannel(StdClassArrayHandler::CHANNEL, ['some']);

        $this->assertEquals([new stdClass()], $handler->received);
    }

    public function test_static_converter_stringable_conversion_do_not_resolve(): void
    {
        $staticConverter = new class () {
            #[Converter]
            public static function convert(string $data): stdClass
            {
                return new stdClass();
            }
        };
        $handler = new class () {
            public stdClass $handled;

            #[CommandHandler('test')]
            public function handler(stdClass $data): void
            {
                $this->handled = $data;
            }
        };

        $stringableObject = new class () {
            public function __toString(): string
            {
                return 'some';
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$staticConverter::class, $handler::class],
            [$handler],
            enabledModules: [],
        );

        $this->expectException(MethodInvocationException::class);
        $this->expectExceptionMessage("Cannot resolve parameter 'data'");

        $ecotone->sendCommandWithRouting('test', $stringableObject);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class StdClassArrayHandler
{
    public const CHANNEL = 'inputChannel';

    /** @var stdClass[] */
    public array $received = [];

    /**
     * @param stdClass[] $value
     */
    #[InternalHandler(self::CHANNEL)]
    public function handle(array $value): void
    {
        $this->received = $value;
    }
}
