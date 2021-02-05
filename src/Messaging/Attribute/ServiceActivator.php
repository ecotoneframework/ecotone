<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

#[\Attribute(\Attribute::TARGET_METHOD)]
class ServiceActivator extends InputOutputEndpointAnnotation
{
    private bool $requiresReply;

    public function __construct(string $inputChannelName, string $endpointId = "", string $outputChannelName = "", bool $requiresReply = false, array $requiredInterceptorNames = [])
    {
        parent::__construct($inputChannelName, $endpointId, $outputChannelName, $requiredInterceptorNames);
        $this->requiresReply = $requiresReply;
    }

    public function isRequiresReply(): bool
    {
        return $this->requiresReply;
    }
}