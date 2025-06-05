<?php

namespace Ecotone\Modelling\Config;

/**
 * licence Apache-2.0
 */
class MessageBusChannel
{
    public const COMMAND_CHANNEL_NAME_BY_OBJECT = self::COMMAND_CHANNEL_NAME_BY_NAME;
    public const COMMAND_CHANNEL_NAME_BY_NAME   = 'ecotone.modelling.bus.command_by_name';

    public const QUERY_CHANNEL_NAME_BY_OBJECT = self::QUERY_CHANNEL_NAME_BY_NAME;
    public const QUERY_CHANNEL_NAME_BY_NAME   = 'ecotone.modelling.bus.query_by_name';

    public const EVENT_CHANNEL_NAME_BY_OBJECT = self::EVENT_CHANNEL_NAME_BY_NAME;
    public const EVENT_CHANNEL_NAME_BY_NAME   = 'ecotone.modelling.bus.event_by_name';
}
