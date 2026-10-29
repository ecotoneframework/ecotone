<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\Attribute\CommandHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Messaging\Fixture\Handler\HeaderConversion\ConvertedHeaderEndpoint;
use Test\Ecotone\Messaging\Fixture\Handler\HeaderConversion\JsonConverter;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class HeaderConversionTest extends TestCase
{
    #[DataProvider('differentDefaultSerializations')]
    public function test_using_scalar_in_metadata_for_conversion(ServiceConfiguration $serviceConfiguration): void
    {
        $convertedHeaderEndpoint = new ConvertedHeaderEndpoint();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConvertedHeaderEndpoint::class, JsonConverter::class],
            [$convertedHeaderEndpoint, new JsonConverter()],
            $serviceConfiguration,
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async'
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('withScalarConversion', metadata: [
                'token' => '537edce7-7e56-4777-b6ec-a012c40b9d1b',
            ])
            ->run('async');

        $this->assertEquals(
            Uuid::fromString('537edce7-7e56-4777-b6ec-a012c40b9d1b'),
            $convertedHeaderEndpoint->result()->toString()
        );
    }

    #[DataProvider('differentDefaultSerializations')]
    public function test_using_object_in_metadata_for_conversion(ServiceConfiguration $serviceConfiguration): void
    {
        $convertedHeaderEndpoint = new ConvertedHeaderEndpoint();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConvertedHeaderEndpoint::class, JsonConverter::class],
            [$convertedHeaderEndpoint, new JsonConverter()],
            $serviceConfiguration,
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async'
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('withScalarConversion', metadata: [
                'token' => Uuid::fromString('537edce7-7e56-4777-b6ec-a012c40b9d1b'),
            ])
            ->run('async');

        $this->assertEquals(
            Uuid::fromString('537edce7-7e56-4777-b6ec-a012c40b9d1b'),
            $convertedHeaderEndpoint->result()->toString()
        );
    }

    #[DataProvider('differentDefaultSerializations')]
    public function test_using_fallback_conversion_to_json(ServiceConfiguration $serviceConfiguration): void
    {
        $convertedHeaderEndpoint = new ConvertedHeaderEndpoint();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConvertedHeaderEndpoint::class, JsonConverter::class],
            [$convertedHeaderEndpoint, new JsonConverter()],
            $serviceConfiguration,
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async'
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('withFallbackConversion', metadata: [
                'tokens' => [1, 2, 3, 4, 5],
            ])
            ->run('async');

        $this->assertEquals(
            [1, 2, 3, 4, 5],
            $convertedHeaderEndpoint->result()
        );
    }

    public function test_converting_scalar_header_to_nullable_enum(): void
    {
        $handler = new class () {
            public ?DeliveryMethod $deliveryMethod = null;

            #[CommandHandler('withNullableEnumConversion', endpointId: 'withNullableEnumConversionEndpoint')]
            public function handle(
                #[Header('deliveryMethod')] ?DeliveryMethod $deliveryMethod = null
            ): void {
                $this->deliveryMethod = $deliveryMethod;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
        );

        $ecotoneLite->sendCommandWithRoutingKey('withNullableEnumConversion', metadata: [
            'deliveryMethod' => DeliveryMethod::Email->value,
        ]);

        self::assertSame(DeliveryMethod::Email, $handler->deliveryMethod);
    }

    /**
     * This will change nothing, as it's used for payload, not header conversion.
     * However to be sure that it's not affecting header conversion, it's part of the test scenario
     */
    public static function differentDefaultSerializations(): iterable
    {
        yield [
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withDefaultSerializationMediaType(MediaType::APPLICATION_X_PHP_SERIALIZED),
        ];
        yield [
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON),
        ];
    }
}

enum DeliveryMethod: string
{
    case Email = 'email';
}
