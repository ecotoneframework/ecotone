<?php

namespace Ecotone\Laravel;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;
use Illuminate\Database\Eloquent\Model;

#[Repository]
class EloquentRepository implements StandardRepository
{
    public function canHandle(string $aggregateClassName): bool
    {
        return is_subclass_of($aggregateClassName, Model::class);
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        return call_user_func([$aggregateClassName, 'find'], array_pop($identifiers));
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void
    {
        $aggregate->save();
    }
}