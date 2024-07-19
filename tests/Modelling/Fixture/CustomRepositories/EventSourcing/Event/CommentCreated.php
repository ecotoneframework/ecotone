<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\EventSourcing\Event;

/**
 * licence Apache-2.0
 */
final class CommentCreated
{
    public function __construct(
        public string $id,
    ) {
    }
}
