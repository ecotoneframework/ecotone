<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EmailNotifier;

use Ecotone\Modelling\Attribute\EventHandler;

class EmailNotifier
{
    private array $emails = [];

    #[EventHandler]
    public function when(EmailNotification $emailNotification): void
    {
        $this->emails[] = $emailNotification;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function reset(): void
    {
        $this->emails = [];
    }
}
