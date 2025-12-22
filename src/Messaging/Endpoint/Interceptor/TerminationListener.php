<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

interface TerminationListener
{
    public function shouldTerminate(): bool;
}
