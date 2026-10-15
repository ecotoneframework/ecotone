<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\EndpointAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
class Orchestrator extends EndpointAnnotation
{
    public function __construct(
        private string $inputChannelName,
        private string $endpointId = '',
    ) {
        parent::__construct($inputChannelName, $endpointId);
    }
}
