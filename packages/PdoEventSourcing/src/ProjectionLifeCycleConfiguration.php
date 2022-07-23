<?php

namespace Ecotone\EventSourcing;

class ProjectionLifeCycleConfiguration
{
    private ?string $initializationRequestChannel = null;
    private ?string $resetRequestChannel = null;
    private ?string $deleteRequestChannel = null;

    private function __construct()
    {
    }

    public static function create(): static
    {
        return new self();
    }

    public function withInitializationRequestChannel(string $initializationRequestChannel): static
    {
        $this->initializationRequestChannel = $initializationRequestChannel;

        return $this;
    }

    public function withDeleteRequestChannel(string $deleteRequestChannel): static
    {
        $this->deleteRequestChannel = $deleteRequestChannel;

        return $this;
    }

    public function withResetRequestChannel(string $resetRequestChannel): static
    {
        $this->resetRequestChannel = $resetRequestChannel;

        return $this;
    }

    public function getInitializationRequestChannel(): ?string
    {
        return $this->initializationRequestChannel;
    }

    public function getRebuildRequestChannel(): ?string
    {
        return $this->resetRequestChannel;
    }

    public function getDeleteRequestChannel(): ?string
    {
        return $this->deleteRequestChannel;
    }
}
