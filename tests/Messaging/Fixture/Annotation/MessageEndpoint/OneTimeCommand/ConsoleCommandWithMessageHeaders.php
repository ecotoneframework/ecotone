<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\Parameter\Header;

final class ConsoleCommandWithMessageHeaders
{
    private array $parameters = [];

    #[ConsoleCommand('cli-command:with-headers')]
    public function withArrayOfOptions(string $content, #[Header('email')] string $email): void
    {
        $this->parameters[] = $content;
        $this->parameters[] = $email;
    }

    #[ConsoleCommand('cli-command:with-multiple-headers')]
    public function withArrayOfMultipleOptions(
        string $content,
        #[Header('supportive_email')] string $supportiveEmail,
        #[Header('billing_email')] string $billingEmail,
    ): void {
        $this->parameters[] = $content;
        $this->parameters[] = $supportiveEmail;
        $this->parameters[] = $billingEmail;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
