<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\EventSourcing\Event;

final class CommentCreated
{
    public function __construct(
        public string $id,
    ) {
    }
}
