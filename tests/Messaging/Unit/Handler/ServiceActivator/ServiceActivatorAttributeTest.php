<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ServiceActivatorAttributeTest extends TestCase
{
    public function test_returning_array_replaces_the_payload(): void
    {
        $handler = new ServiceActivatorHandler();
        $ecotone = $this->bootstrap([ServiceActivatorHandler::class], [$handler]);

        $result = $ecotone->sendDirectToChannel(ServiceActivatorHandler::ARRAY_CHANNEL, 'test');

        $this->assertSame(['some' => 'test'], $result);
    }

    public function test_returning_array_with_changing_headers_merges_into_the_message_headers_instead(): void
    {
        $handler = new ServiceActivatorHandler();
        $ecotone = $this->bootstrap([ServiceActivatorHandler::class], [$handler]);

        $message = $ecotone->sendDirectToChannelWithMessageReply(ServiceActivatorHandler::CHANGING_HEADERS_CHANNEL, 'test');

        $this->assertSame('test', $message->getPayload());
        $this->assertSame('test', $message->getHeaders()->get('some'));
    }

    public function test_around_interceptor_chain_wraps_the_endpoint_by_ascending_precedence(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [MathHandler::class, MathInterceptors::class],
            [new MathHandler(), new MathInterceptors(2)],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $this->assertSame(8, $ecotone->sendDirectToChannel(MathHandler::MATH_CHANNEL, 1));
    }

    private function bootstrap(array $classesToResolve, array $services)
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            ServiceConfiguration::createWithDefaults()->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withEnabledModulePackageNames([]),
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ServiceActivatorHandler
{
    public const ARRAY_CHANNEL = 'serviceActivator.array';
    public const CHANGING_HEADERS_CHANNEL = 'serviceActivator.changingHeaders';

    #[InternalHandler(self::ARRAY_CHANNEL)]
    public function arrayReturnValue(string $payload): array
    {
        return ['some' => $payload];
    }

    #[InternalHandler(self::CHANGING_HEADERS_CHANNEL, changingHeaders: true)]
    public function arrayReturnValueAsHeaders(string $payload): array
    {
        return ['some' => $payload];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class MathHandler
{
    public const MATH_CHANNEL = 'math.channel';

    #[InternalHandler(self::MATH_CHANNEL)]
    public function result(int $amount): int
    {
        return $amount;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class MathInterceptors
{
    public function __construct(private int $secondValueForMathOperations)
    {
    }

    #[Around(precedence: 1, pointcut: MathHandler::class)]
    public function sum(MethodInvocation $methodInvocation): int
    {
        return $methodInvocation->proceed() + $this->secondValueForMathOperations;
    }

    #[Around(precedence: 2, pointcut: MathHandler::class)]
    public function multiply(MethodInvocation $methodInvocation): int
    {
        return $methodInvocation->proceed() * $this->secondValueForMathOperations;
    }

    #[Around(precedence: 3, pointcut: MathHandler::class)]
    public function sumAgain(MethodInvocation $methodInvocation): int
    {
        return $methodInvocation->proceed() + $this->secondValueForMathOperations;
    }
}
