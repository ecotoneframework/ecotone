<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Projecting\ProjectionRegistry;
use InvalidArgumentException;

class ProjectingConsoleCommands
{
    public function __construct(private ProjectionRegistry $registry)
    {
    }

    #[ConsoleCommand('ecotone:projection:init')]
    public function initProjection(string $name): void
    {
        if (! $this->registry->has($name)) {
            throw new InvalidArgumentException("There is no projection with name {$name}");
        }
        $this->registry->get($name)->init();
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
