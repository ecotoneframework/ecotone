<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow;

use Ecotone\Api\Attribute\Version;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
final class AggregateVersionProperty
{
    private function __construct(
        public readonly string $propertyName,
        public readonly bool $autoIncrease,
    ) {
    }

    public static function resolveFor(ClassDefinition $aggregateClassDefinition): ?self
    {
        $versionAnnotation = Type::attribute(Version::class);
        $versionProperties = [];
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $versionProperties[$property->getName()] = $property->getAnnotation($versionAnnotation);
            }
        }

        if (count($versionProperties) > 1) {
            throw ConfigurationException::create(sprintf(
                '%s declares #[Version] on two properties, `%s`, yet an aggregate has one version: Ecotone compares it on save and increases it on every change. Keep #[Version] on one property and remove it from the other, or use the %s trait, which declares the single version property.',
                $aggregateClassDefinition->getClassType()->toString(),
                implode('` and `', array_keys($versionProperties)),
                WithAggregateVersioning::class,
            ));
        }

        foreach ($versionProperties as $propertyName => $annotation) {
            return new self($propertyName, $annotation->autoIncrease);
        }

        return null;
    }
}
