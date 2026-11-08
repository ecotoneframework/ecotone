<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
enum FlowEdgeKind: string
{
    case ROUTED_TO = 'routedTo';
    case INPUT = 'input';
    case OUTPUT = 'output';
    case ASYNCHRONOUS = 'asynchronous';
    case SENDS_TO = 'sendsTo';
    case APPLIES = 'applies';
    case EMITS = 'emits';
    case CALLS = 'calls';
    case ROUTES_TO = 'routesTo';
    case DISTRIBUTES = 'distributes';
    case INTERCEPTED_BY = 'interceptedBy';
    case PROJECTS_INTO = 'projectsInto';
}
