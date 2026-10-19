<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
final class ProjectionOperationEndpoint
{
    public const INITIALIZE = 'initialize';
    public const TRIGGER = 'trigger';
    public const RESET = 'reset';
    public const BACKFILL = 'backfill';
    public const REBUILD = 'rebuild';
    public const DELETE = 'delete';

    public function __construct(public readonly string $projectionName, public readonly string $operation)
    {
    }

    public static function channelFor(string $projectionName, string $operation): string
    {
        return 'projecting_manager_' . $operation . ':' . $projectionName;
    }
}
