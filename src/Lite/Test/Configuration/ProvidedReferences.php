<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Api\ExtensionObject\ConnectionReference;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
final class ProvidedReferences
{
    /**
     * @param object[] $extensionObjects
     */
    public function __construct(private ContainerInterface $externalContainer, private array $extensionObjects)
    {
    }

    public function has(string $referenceName): bool
    {
        if ($this->externalContainer->has($referenceName)) {
            return true;
        }

        foreach ($this->extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ConnectionReference && $extensionObject->getReferenceName() === $referenceName) {
                return true;
            }
        }

        return false;
    }
}
