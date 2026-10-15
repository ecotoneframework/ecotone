<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use Ecotone\Messaging\Config\ConfigurationException;

/**
 * licence Apache-2.0
 */
final class DynamicConsistencyBoundaryDisabled
{
    public const MESSAGE = 'Dynamic Consistency Boundary is disabled. Register DynamicConsistencyBoundaryConfiguration::createWithDefaults() as an extension object (#[ServiceContext]) to enable decision models, event tags and append conditions.';

    public static function exception(): ConfigurationException
    {
        return ConfigurationException::create(self::MESSAGE);
    }
}
