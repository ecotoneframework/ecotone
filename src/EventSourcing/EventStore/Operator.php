<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore;

/**
 * licence Apache-2.0
 */
enum Operator: string
{
    case EQUALS = '=';

    case GREATER_THAN = '>';

    case GREATER_THAN_EQUALS = '>=';

    case IN = 'in';

    case LOWER_THAN = '<';

    case LOWER_THAN_EQUALS = '<=';

    case NOT_EQUALS = '!=';

    case NOT_IN = 'nin';

    case REGEX = 'regex';
}
