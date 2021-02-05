<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Router extends EndpointAnnotation
{
    private bool $isResolutionRequired = true;

    public function __construct(string $inputChannelName, string $endpointId, bool $isResolutionRequired)
    {
        parent::__construct($inputChannelName, $endpointId);
        $this->isResolutionRequired = $isResolutionRequired;
    }

    public function isResolutionRequired(): bool
    {
        return $this->isResolutionRequired;
    }
}