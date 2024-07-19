<?php

namespace Ecotone\Messaging\Handler;

/**
 * licence Apache-2.0
 */
interface NonProxyGateway
{
    public function execute(array $methodArgumentValues);
}
