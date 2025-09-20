<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

interface Transaction
{
    public function commit(): void;
    public function rollBack(): void;
}
