<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit\Versioning;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class ApplyMethodMetadataWithoutLicenceTest extends TestCase
{
    public function test_reading_metadata_in_apply_method_without_licence_names_the_open_source_alternative(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting([BasketReadingRecordTime::class], enabledModules: []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Using multiple parameters for #[Apply] method: ' . BasketReadingRecordTime::class . '::applyCreated is part of Enterprise features. Without Enterprise, keep a single event parameter and carry the value you need (for example the time of the change) in the event itself.');

        $ecotone->sendCommandWithRouting('basketReadingRecordTime.create', 'basket-1');
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
#[EventSourcingAggregate]
final class BasketReadingRecordTime
{
    use WithAggregateVersioning;

    #[Identifier]
    public string $basketId;

    public ?int $lastChangedAt = null;

    #[CommandHandler('basketReadingRecordTime.create')]
    public static function create(string $basketId): array
    {
        return [new BasketReadingRecordTimeCreated($basketId)];
    }

    #[Apply]
    public function applyCreated(BasketReadingRecordTimeCreated $event, #[Header(MessageHeaders::TIMESTAMP)] int $recordedAt): void
    {
        $this->basketId = $event->basketId;
        $this->lastChangedAt = $recordedAt;
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class BasketReadingRecordTimeCreated
{
    public function __construct(public string $basketId)
    {
    }
}
