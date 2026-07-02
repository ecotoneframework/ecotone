<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
final class ExternalReferenceResolver
{
    public const TESTING_ALIAS_PREFIX = 'ecotone.testing.';

    public static function resolve(ContainerInterface $externalContainer, string $id, int $invalidBehavior): mixed
    {
        if ($externalContainer->has($id)) {
            return $externalContainer->get($id);
        }
        if ($externalContainer->has(self::TESTING_ALIAS_PREFIX . $id)) {
            return $externalContainer->get(self::TESTING_ALIAS_PREFIX . $id);
        }
        if ($invalidBehavior === ContainerImplementation::NULL_ON_INVALID_REFERENCE) {
            return null;
        }

        throw new InvalidArgumentException("Reference {$id} was not found in definitions");
    }
}
