<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
class OrchestratorGateway
{
    public function __construct()
    {

    }
}
