<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

/**
 * licence Apache-2.0
 */
final class GatheringHeaders
{
    public const MANUAL_RELEASE = 'ecotone_gathering_group_manual_release';

    public const TIMEOUT = 'ecotone_gathering_group_timeout';

    public const GROUP_ID = 'ecotone_gathering_group_id';
    public const PART_NUMBER = 'ecotone_gathering_part_number';
    public const SIZE = 'ecotone_gathering_group_size';
    public const CHANNEL_NAME = 'ecotone_gathering_channel_name';
    public const NO_RESULT = 'ecotone_gathering_part_no_result';
}
