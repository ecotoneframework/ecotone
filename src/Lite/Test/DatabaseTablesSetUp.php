<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

/**
 * licence Apache-2.0
 */
interface DatabaseTablesSetUp
{
    public function setUpEveryTable(): void;

    public function setUpTablesOfProvidedConnections(): void;
}
