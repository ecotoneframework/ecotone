<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
enum FlowNodeKind: string
{
    case COMMAND = 'command';
    case EVENT = 'event';
    case QUERY = 'query';
    case COMMAND_HANDLER = 'commandHandler';
    case EVENT_HANDLER = 'eventHandler';
    case QUERY_HANDLER = 'queryHandler';
    case INTERNAL_HANDLER = 'internalHandler';
    case ROUTER = 'router';
    case SPLITTER = 'splitter';
    case TRANSFORMER = 'transformer';
    case ORCHESTRATOR = 'orchestrator';
    case AGGREGATE = 'aggregate';
    case SAGA = 'saga';
    case PROJECTION = 'projection';
    case GATEWAY = 'gateway';
    case CHANNEL = 'channel';
    case SCHEDULED = 'scheduled';
    case DISTRIBUTED_BUS = 'distributedBus';
    case INTERCEPTOR = 'interceptor';

    public function isMessage(): bool
    {
        return in_array($this, [self::COMMAND, self::EVENT, self::QUERY], true);
    }

    public function isHandler(): bool
    {
        return in_array($this, [self::COMMAND_HANDLER, self::EVENT_HANDLER, self::QUERY_HANDLER, self::INTERNAL_HANDLER, self::ROUTER, self::SPLITTER, self::TRANSFORMER, self::ORCHESTRATOR], true);
    }

    public function isOwner(): bool
    {
        return in_array($this, [self::AGGREGATE, self::SAGA, self::PROJECTION], true);
    }
}
