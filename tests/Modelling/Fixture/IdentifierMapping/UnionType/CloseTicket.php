<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\UnionType;

/**
 * licence Apache-2.0
 */
final class CloseTicket
{
    public function __construct(public readonly string $ticketId)
    {
    }
}
