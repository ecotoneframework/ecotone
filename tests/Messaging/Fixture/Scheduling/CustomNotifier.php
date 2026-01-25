<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Scheduling;

/*
 * licence Apache-2.0
 */
class CustomNotifier
{
    private array $notifications = [];

    public function notify(string $eventName, $value): void
    {
        if (! isset($this->notifications[$eventName])) {
            $this->notifications[$eventName] = [];
        }

        $this->notifications[$eventName][] = $value;
    }

    public function getNotificationsOf(string $eventName): array
    {
        return $this->notifications[$eventName] ?? [];
    }
}
