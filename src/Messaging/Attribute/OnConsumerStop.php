<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class OnConsumerStop extends ServiceActivator
{
    public const CONSUMER_STOP_CHANNEL_NAME = 'ecotone.consumer_lifecycle.stop';

    public function __construct()
    {
        parent::__construct(self::CONSUMER_STOP_CHANNEL_NAME, '', '', false, []);
    }
}
