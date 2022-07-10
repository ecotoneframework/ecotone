<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

#[\Attribute(\Attribute::TARGET_METHOD)]
class MessageConsumer
{
    private string $endpointId;

    public function __construct(string $endpointId)
    {
        $this->endpointId          = $endpointId;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }
}