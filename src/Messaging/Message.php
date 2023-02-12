<?php

namespace Ecotone\Messaging;

interface Message
{
    public function getHeaders(): MessageHeaders;

    public function getPayload(): mixed;
}
