<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

interface TerminationListener
{
    public function shouldTerminate(): bool;
}
