<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ramsey\Uuid\Uuid;

#[Attribute()]
class IdentifiedAnnotation
{
    private string $endpointId = '';
    private bool $isGenerated = false;

    public function __construct(string $endpointId = '')
    {
        $this->endpointId = $endpointId;

        if (! $this->endpointId) {
            $this->endpointId = Uuid::uuid4()->toString();
            $this->isGenerated = true;
        }
    }

    public function isEndpointIdGenerated(): bool
    {
        return $this->isGenerated;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }
}
