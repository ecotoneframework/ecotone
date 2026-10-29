<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\UnionType;

use Ecotone\Modelling\Attribute\TargetIdentifier;

/**
 * licence Apache-2.0
 */
final class CloseTicketByTargetIdentifier
{
    public function __construct(#[TargetIdentifier('ticketId')] public readonly string $id)
    {
    }
}
