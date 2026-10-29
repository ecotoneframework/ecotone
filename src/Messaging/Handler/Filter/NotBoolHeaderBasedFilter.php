<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;

/**
 * Filter that returns the NEGATED value of a boolean header.
 * When header is true, filter returns false (pass through).
 * When header is false, filter returns true (discard).
 *
 * licence Apache-2.0
 */
final class NotBoolHeaderBasedFilter implements DefinedObject
{
    public function __construct(private string $headerName, private ?bool $defaultResultWhenHeaderIsMissing)
    {
    }

    public function filter(Message $message): bool
    {
        if (! is_null($this->defaultResultWhenHeaderIsMissing) && ! $message->getHeaders()->containsKey($this->headerName)) {
            return $this->defaultResultWhenHeaderIsMissing;
        }

        return ! (bool)$message->getHeaders()->get($this->headerName);
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->headerName,
            $this->defaultResultWhenHeaderIsMissing,
        ]);
    }
}
