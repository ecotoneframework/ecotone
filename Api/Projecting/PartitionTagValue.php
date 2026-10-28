<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use Ecotone\Api\Attribute\Header;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class PartitionTagValue extends Header
{
    public const HEADER_NAME = 'ecotone_projection_partition_tag_value';

    public function __construct()
    {
    }

    public function getHeaderName(): string
    {
        return self::HEADER_NAME;
    }
}
