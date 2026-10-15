<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\EventSourcing;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;
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

    #[Apply]
    public function applyCommentCreated(CommentCreated $event): void
    {
        $this->id = $event->id;
    }
}
