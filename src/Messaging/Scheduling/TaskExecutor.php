<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Scheduling;

use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * licence Apache-2.0
 */
interface TaskExecutor
{
    public function execute(PollingMetadata $pollingMetadata): void;
}
