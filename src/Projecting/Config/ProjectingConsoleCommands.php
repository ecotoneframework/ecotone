<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Projecting\ProjectionRegistry;
use InvalidArgumentException;

class ProjectingConsoleCommands
{
    public function __construct(private ProjectionRegistry $registry)
    {
    }

    #[ConsoleCommand('ecotone:projection:init')]
    public function initProjection(?string $name = null, #[ConsoleParameterOption] bool $all = false): void
    {
        if ($name === null) {
            if (! $all) {
                throw new InvalidArgumentException('You need to provide projection name or use --all option');
            }
            foreach ($this->registry->projectionNames() as $projection) {
                $this->registry->get($projection)->init();
            }
        } else {
            if (! $this->registry->has($name)) {
                throw new InvalidArgumentException("There is no projection with name {$name}");
            }
            $this->registry->get($name)->init();
        }
    }

    #[ConsoleCommand('ecotone:projection:backfill')]
    public function backfillProjection(string $name): void
    {
        if (! $this->registry->has($name)) {
            throw new InvalidArgumentException("There is no projection with name {$name}");
        }
        $this->registry->get($name)->backfill();
    }

    #[ConsoleCommand('ecotone:projection:delete')]
    public function deleteProjection(string $name): void
    {
        if (! $this->registry->has($name)) {
            throw new InvalidArgumentException("There is no projection with name {$name}");
        }
        $this->registry->get($name)->delete();
    }
}
