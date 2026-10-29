<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class PolicyDrivenTokenService
{
    private array $tokens = [];

    #[TokenPolicy(casing: 'upper')]
    #[CommandHandler('policyToken.store')]
    public function store(
        #[Header('token', expression: static function (TokenPolicy $policy, #[Header('token')] string $token): string {
            return $policy->casing === 'upper' ? strtoupper($token) : strtolower($token);
        })] string $token,
    ): void {
        $this->tokens[] = $token;
    }

    #[QueryHandler('policyToken.getTokens')]
    public function getTokens(): array
    {
        return $this->tokens;
    }
}
