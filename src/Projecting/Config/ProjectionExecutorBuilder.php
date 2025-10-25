<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

interface ProjectionExecutorBuilder extends CompilableBuilder
{
    public function projectionName(): string;
    public function asyncChannelName(): ?string;
    public function partitionHeader(): ?string;
    public function automaticInitialization(): bool;
    public function batchSize(): int;
}
