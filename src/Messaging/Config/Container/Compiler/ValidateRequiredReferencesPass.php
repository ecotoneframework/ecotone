<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Psr\Container\ContainerInterface;

/**
 * Validates that all required references are registered in the container.
 * If a required reference is missing, throws a ConfigurationException with a user-friendly error message.
 *
 * licence Apache-2.0
 */
class ValidateRequiredReferencesPass implements CompilerPass
{
    /**
     * @param array<string, string> $requiredReferences Map of referenceId => errorMessage
     * @param ContainerInterface|null $externalContainer External container to check for references
     */
    public function __construct(
        private array $requiredReferences,
        private bool $isWorkingInTestMode,
        private ?ContainerInterface $externalContainer
    ) {
    }

    public function process(ContainerBuilder $builder): void
    {
        if ($this->isWorkingInTestMode || $this->externalContainer === null) {
            return;
        }

        $definitions = $builder->getDefinitions();
        $externalReferences = $builder->getExternalReferences();

        foreach ($this->requiredReferences as $referenceId => $errorMessage) {
            $existsInDefinitions = isset($definitions[$referenceId]);
            $existsInExternalReferences = isset($externalReferences[$referenceId]);
            $existsInExternalContainer = $this->externalContainer !== null && $this->externalContainer->has($referenceId);

            if (! $existsInDefinitions && ! $existsInExternalReferences && ! $existsInExternalContainer) {
                throw ConfigurationException::create($errorMessage);
            }
        }
    }
}
