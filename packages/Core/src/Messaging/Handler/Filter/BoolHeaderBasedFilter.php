<?php

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Message;

final class BoolHeaderBasedFilter
{
    public function __construct(private string $headerName, private ?bool $defaultResultWhenHeaderIsMissing) {}

    public function filter(Message $message): bool
    {
        if (!is_null($this->defaultResultWhenHeaderIsMissing) && !$message->getHeaders()->containsKey($this->headerName)) {
            return $this->defaultResultWhenHeaderIsMissing;
        }

        return (bool)$message->getHeaders()->get($this->headerName);
    }
}