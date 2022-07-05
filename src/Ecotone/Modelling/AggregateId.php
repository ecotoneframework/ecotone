<?php

namespace Ecotone\Modelling;

class AggregateId
{
    public static function resolve(string $aggregateClass, $id)
    {
        if (is_object($id)) {
            if (!method_exists($id, '__toString')) {
                throw NoCorrectIdentifierDefinedException::create(sprintf("Aggregate %s has identifier which is class. You must define __toString method for %s", $aggregateClass, get_class($id)));
            }

            return (string)$id;
        }
        if (is_array($id)) {
            throw NoCorrectIdentifierDefinedException::create(sprintf("Aggregate %s has identifier which is array. Array is not allowed as identifier", $aggregateClass));
        }

        return $id;
    }

    public static function resolveArrayOfIdentifiers(string $aggregateClass, array $ids): array
    {
        $resolvedIdentifiers = [];
        foreach ($ids as $name => $id) {
            $resolvedIdentifiers[$name] = self::resolve($aggregateClass, $id);
        }

        return $resolvedIdentifiers;
    }
}