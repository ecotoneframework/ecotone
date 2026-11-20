<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\MetadataPropagator;

use Ecotone\Messaging\Execution\CopiedToChildExecution;

/**
 * licence Apache-2.0
 */
final class PropagatedHeaders implements CopiedToChildExecution
{
    /** @var array<int, array<string, mixed>> */
    public array $headersOfHandledMessages = [];
    /** @var MessageCausation[] */
    public array $causationChain = [];
    public bool $isPollingConsumer = false;
    public int $lastCoroutineStartedBeforeTheHandledMessage = 0;

    public function copyForChildExecution(int $childCoroutineId): ?object
    {
        if ($this->headersOfHandledMessages === [] || $childCoroutineId <= $this->lastCoroutineStartedBeforeTheHandledMessage) {
            return null;
        }

        $copy = new self();
        $copy->headersOfHandledMessages = [end($this->headersOfHandledMessages)];
        $copy->causationChain = $this->causationChain;
        $copy->lastCoroutineStartedBeforeTheHandledMessage = $this->lastCoroutineStartedBeforeTheHandledMessage;

        return $copy;
    }
}
