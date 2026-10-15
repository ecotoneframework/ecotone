<?php

namespace Ecotone\Api\Messaging;

/**
 * licence Apache-2.0
 */
interface Future
{
    /**
     * @return mixed
     */
    public function resolve();
}
