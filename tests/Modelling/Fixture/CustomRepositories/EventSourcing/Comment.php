<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\EventSourcing;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;
use Test\Ecotone\Modelling\Fixture\CustomRepositories\EventSourcing\Event\CommentCreated;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
final class Comment
{
    use WithAggregateVersioning;

    #[Identifier] private string $id;

    #[CommandHandler('create.comment')]
    public static function create(string $id): array
    {
        return [new CommentCreated($id)];
    }

    #[EventSourcingHandler]
    public function applyCommentCreated(CommentCreated $event): void
    {
        $this->id = $event->id;
    }
}
