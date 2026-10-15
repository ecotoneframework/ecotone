<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class UniqueUsernamePerTenantTest extends TestCase
{
    public function test_the_first_claim_of_a_username_succeeds_when_no_counter_row_exists_yet(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'acme']);

        $this->assertSame(['ada'], $this->claimedUsernamesOf($ecotone, 'acme'));
    }

    public function test_claiming_a_username_already_taken_in_the_same_tenant_is_refused(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'acme']);

        $this->expectException(UsernameAlreadyTaken::class);

        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'acme']);
    }

    public function test_a_claim_landing_while_another_claim_of_the_same_username_is_being_decided_conflicts(): void
    {
        $ecotone = $this->bootstrap();

        try {
            $ecotone->sendCommand(new ClaimUsernameWhileAnotherClaimLands('ada'), metadata: ['tenant' => 'acme']);
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('on tag username:ada', $exception->getMessage());
            $this->assertStringContainsString('while deciding ' . UsernameAvailability::class, $exception->getMessage());
        }

        $this->assertSame(['ada'], $this->claimedUsernamesOf($ecotone, 'acme'));
    }

    public function test_two_different_usernames_claimed_in_one_tenant_do_not_conflict_on_the_filter_only_tenant_tag(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->sendCommand(new ClaimUsernameWhileAnotherUsernameIsClaimed('ada', 'grace'), metadata: ['tenant' => 'acme']);

        $this->assertSame(['grace', 'ada'], $this->claimedUsernamesOf($ecotone, 'acme'));
    }

    public function test_a_released_username_can_be_claimed_again(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'acme']);
        $ecotone->sendCommand(new ReleaseUsername('ada'), metadata: ['tenant' => 'acme']);

        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'acme']);

        $this->assertSame(['ada', 'ada'], $this->claimedUsernamesOf($ecotone, 'acme'));
    }

    public function test_the_same_username_in_another_tenant_does_not_conflict(): void
    {
        $ecotone = $this->bootstrap();
        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'acme']);

        $ecotone->sendCommand(new ClaimUsername('ada'), metadata: ['tenant' => 'globex']);

        $this->assertSame(['ada'], $this->claimedUsernamesOf($ecotone, 'acme'));
        $this->assertSame(['ada'], $this->claimedUsernamesOf($ecotone, 'globex'));
    }

    public function test_a_model_declaring_the_same_scope_in_the_other_order_behaves_the_same(): void
    {
        $ecotone = $this->bootstrap(tenantFirst: true);

        $ecotone->sendCommand(new ClaimUsernameScopedTenantFirst('ada'), metadata: ['tenant' => 'acme']);

        $this->expectException(UsernameAlreadyTaken::class);

        $ecotone->sendCommand(new ClaimUsernameScopedTenantFirst('ada'), metadata: ['tenant' => 'acme']);
    }

    /**
     * @return string[]
     */
    private function claimedUsernamesOf(FlowTestSupport $ecotone, string $tenant): array
    {
        $events = $ecotone->getGateway(EventStore::class)
            ->loadByCriteria(EventCriteria::tag('tenant', $tenant)->ofTypes(UsernameClaimed::class))
            ->events;

        return array_map(static fn (object $event): string => $event->getPayload()->username, $events);
    }

    private function bootstrap(bool $tenantFirst = false): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                UsernameClaimed::class,
                UsernameReleased::class,
                ...($tenantFirst
                    ? [UsernameAvailabilityScopedTenantFirst::class, UsernameRegistrationScopedTenantFirst::class]
                    : [UsernameAvailability::class, UsernameRegistration::class]),
            ],
            containerOrAvailableServices: $tenantFirst ? [new UsernameRegistrationScopedTenantFirst()] : [new UsernameRegistration()],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['tenant']),
            ])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class ClaimUsername
{
    public function __construct(public string $username)
    {
    }
}

final readonly class ClaimUsernameWhileAnotherClaimLands
{
    public function __construct(public string $username)
    {
    }
}

final readonly class ClaimUsernameWhileAnotherUsernameIsClaimed
{
    public function __construct(
        public string $username,
        public string $otherUsername,
    ) {
    }
}

final readonly class ReleaseUsername
{
    public function __construct(public string $username)
    {
    }
}

final readonly class ClaimUsernameScopedTenantFirst
{
    public function __construct(public string $username)
    {
    }
}

final readonly class UsernameClaimed
{
    public function __construct(
        #[EventTag('tenant')] public string $tenant,
        #[EventTag('username')] public string $username,
    ) {
    }
}

final readonly class UsernameReleased
{
    public function __construct(
        #[EventTag('tenant')] public string $tenant,
        #[EventTag('username')] public string $username,
    ) {
    }
}

/**
 * @internal
 */
final class UsernameAlreadyTaken extends RuntimeException
{
}

/**
 * @internal
 */
final class UsernameNotTaken extends RuntimeException
{
}

#[DecisionModel(tags: ['username', 'tenant'])]
final class UsernameAvailability
{
    private bool $taken = false;

    #[Apply]
    public function claimed(UsernameClaimed $event): void
    {
        $this->taken = true;
    }

    #[Apply]
    public function released(UsernameReleased $event): void
    {
        $this->taken = false;
    }

    public function isTaken(): bool
    {
        return $this->taken;
    }
}

final class UsernameRegistration
{
    #[CommandHandler]
    public function claim(
        ClaimUsername $command,
        #[Fetch("{'tenant': headers['tenant'], 'username': payload.username}")] UsernameAvailability $availability,
        #[Header('tenant')] string $tenant,
    ): array {
        if ($availability->isTaken()) {
            throw new UsernameAlreadyTaken();
        }

        return [new UsernameClaimed($tenant, $command->username)];
    }

    #[CommandHandler]
    public function claimWhileAnotherClaimLands(
        ClaimUsernameWhileAnotherClaimLands $command,
        #[Fetch("{'tenant': headers['tenant'], 'username': payload.username}")] UsernameAvailability $availability,
        #[Header('tenant')] string $tenant,
        CommandBus $commandBus,
    ): array {
        if ($availability->isTaken()) {
            throw new UsernameAlreadyTaken();
        }

        $commandBus->send(new ClaimUsername($command->username), ['tenant' => $tenant]);

        return [new UsernameClaimed($tenant, $command->username)];
    }

    #[CommandHandler]
    public function claimWhileAnotherUsernameIsClaimed(
        ClaimUsernameWhileAnotherUsernameIsClaimed $command,
        #[Fetch("{'tenant': headers['tenant'], 'username': payload.username}")] UsernameAvailability $availability,
        #[Header('tenant')] string $tenant,
        CommandBus $commandBus,
    ): array {
        $commandBus->send(new ClaimUsername($command->otherUsername), ['tenant' => $tenant]);

        return [new UsernameClaimed($tenant, $command->username)];
    }

    #[CommandHandler]
    public function release(
        ReleaseUsername $command,
        #[Fetch("{'tenant': headers['tenant'], 'username': payload.username}")] UsernameAvailability $availability,
        #[Header('tenant')] string $tenant,
    ): array {
        if (! $availability->isTaken()) {
            throw new UsernameNotTaken();
        }

        return [new UsernameReleased($tenant, $command->username)];
    }
}

#[DecisionModel(tags: ['tenant', 'username'])]
final class UsernameAvailabilityScopedTenantFirst
{
    private bool $taken = false;

    #[Apply]
    public function claimed(UsernameClaimed $event): void
    {
        $this->taken = true;
    }

    #[Apply]
    public function released(UsernameReleased $event): void
    {
        $this->taken = false;
    }

    public function isTaken(): bool
    {
        return $this->taken;
    }
}

final class UsernameRegistrationScopedTenantFirst
{
    #[CommandHandler]
    public function claim(
        ClaimUsernameScopedTenantFirst $command,
        #[Fetch("{'tenant': headers['tenant'], 'username': payload.username}")] UsernameAvailabilityScopedTenantFirst $availability,
        #[Header('tenant')] string $tenant,
    ): array {
        if ($availability->isTaken()) {
            throw new UsernameAlreadyTaken();
        }

        return [new UsernameClaimed($tenant, $command->username)];
    }
}
