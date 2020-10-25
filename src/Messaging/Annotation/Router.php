<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Router extends EndpointAnnotation
{
    public bool $isResolutionRequired = true;

    public function __construct(string $inputChannelName, bool $isResolutionRequired)
    {
        $this->inputChannelName = $inputChannelName;
        $this->isResolutionRequired = $isResolutionRequired;
    }

    public function isResolutionRequired(): bool
    {
        return $this->isResolutionRequired;
    }
}