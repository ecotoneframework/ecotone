<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\Api\Attribute\ConsoleCommand;
use Ecotone\Api\Attribute\ConsoleParameterOption;
use Ecotone\Projecting\ProjectionOperations;
use InvalidArgumentException;

class ProjectingConsoleCommands
{
    public function __construct(private ProjectionOperations $operations)
    {
    }

    #[ConsoleCommand('ecotone:projection:init', 'Initializes given projection or all projections with --all')]
    public function initProjection(?string $name = null, #[ConsoleParameterOption] bool $all = false): void
    {
        if ($name !== null) {
            $this->operations->initialize($name);

            return;
        }
        if (! $all) {
            throw new InvalidArgumentException('You need to provide projection name or use --all option');
        }
        $this->operations->initializeAll();
    }

    #[ConsoleCommand('ecotone:projection:backfill', 'Prepares given projection to be backfilled with historical events')]
    public function backfillProjection(string $name): void
    {
        $this->operations->backfill($name);
    }

    #[ConsoleCommand('ecotone:projection:rebuild', 'Prepares given projection to be rebuilt from scratch')]
    public function rebuildProjection(string $name): void
    {
        $this->operations->rebuild($name);
    }

    #[ConsoleCommand('ecotone:projection:delete', 'Deletes given projection')]
    public function deleteProjection(string $name): void
    {
        $this->operations->delete($name);
    }
}
