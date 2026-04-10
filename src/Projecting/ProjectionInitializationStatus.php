<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

enum ProjectionInitializationStatus: string
{
    case INITIALIZED = 'initialized';
    case UNINITIALIZED = 'uninitialized';
}
