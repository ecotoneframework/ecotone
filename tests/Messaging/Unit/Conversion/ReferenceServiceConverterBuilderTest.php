<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Conversion;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleIncorrectConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleIncorrectUnionReturnTypeConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleIncorrectUnionSourceTypeConverterService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class ReferenceServiceConverterBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ReferenceServiceConverterBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_converting_using_reference_service()
    {
        $messaging = ComponentTestBuilder::create([ExampleConverterService::class])
            ->withReference('exampleConverterService', new ExampleConverterService())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayStdClasses')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();

        $this->assertEquals(
            [new stdClass()],
            $messaging->sendDirectToChannel(
                $inputChannel,
                ['some']
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_there_is_more_parameters_than_one_in_converter_reference()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create([ExampleIncorrectConverterService::class])
            ->withReference(ExampleIncorrectConverterService::class, new ExampleIncorrectConverterService())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayStdClasses')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();
    }

    public function test_not_throwing_exception_if_converter_containing_source_union_source_type()
    {
        ComponentTestBuilder::create([ExampleIncorrectUnionSourceTypeConverterService::class])
            ->withReference(ExampleIncorrectUnionSourceTypeConverterService::class, new ExampleIncorrectUnionSourceTypeConverterService())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayStdClasses')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();

        $this->expectNotToPerformAssertions();
    }

    public function test_throwing_exception_if_converter_returning_union_source_type()
    {
        $this->expectException(InvalidArgumentException::class);
        ComponentTestBuilder::create([ExampleIncorrectUnionReturnTypeConverterService::class])
            ->withReference(ExampleIncorrectUnionReturnTypeConverterService::class, new ExampleIncorrectUnionReturnTypeConverterService())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayStdClasses')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();
    }

    public function test_throwing_exception_if_converter_containing_union_target_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create([ExampleIncorrectUnionReturnTypeConverterService::class])
            ->withReference(ExampleIncorrectUnionReturnTypeConverterService::class, new ExampleIncorrectUnionReturnTypeConverterService())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayStdClasses')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();
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

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$staticConverter::class],
            [$staticConverter],
            configuration: ServiceConfiguration::createWithDefaults()
                ->addExtensionObject(
                    ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withArrayStdClasses')
                        ->withInputChannelName($inputChannel = 'inputChannel')
                )
        );

        $this->assertEquals(
            [new stdClass()],
            $ecotone->sendDirectToChannel(
                $inputChannel,
                ['some']
            )
        );
    }
}
