<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

final class AuditLog
{
    private array $audit = [];

    #[InternalHandler('audit')]
    public function handle(mixed $data): void
    {
        $this->audit[] = $data;
    }

    #[QueryHandler('audit.getData')]
    public function getAudit(): array
    {
        return $this->audit;
    }
}
