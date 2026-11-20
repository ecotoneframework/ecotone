<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Converter;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ExpressionResultConversionTest extends TestCase
{
    public function test_reference_expression_result_is_converted_to_the_parameter_type_with_a_registered_converter(): void
    {
        $handler = new class () {
            public ?TenantInvoicing $invoicing = null;

            #[CommandHandler('invoice.issue')]
            public function issue(
                #[Reference(QueryBus::class, "service.sendWithRouting('tenant.invoicing')")] TenantInvoicing $invoicing,
            ): void {
                $this->invoicing = $invoicing;
            }

            #[QueryHandler('tenant.invoicing')]
            public function invoicingOf(#[Header('tenantId')] string $tenantId): array
            {
                return ['tenantId' => $tenantId, 'currency' => 'eur'];
            }

            #[Converter]
            public function toTenantInvoicing(array $invoicing): TenantInvoicing
            {
                return new TenantInvoicing($invoicing['tenantId'], $invoicing['currency']);
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        $ecotone->sendCommandWithRouting('invoice.issue', metadata: ['tenantId' => 'tenant-a']);

        self::assertEquals(new TenantInvoicing('tenant-a', 'eur'), $handler->invoicing);
    }

    public function test_reference_expression_scalar_result_is_converted_to_an_enum_parameter(): void
    {
        $handler = new class () {
            public ?NotificationChannel $channel = null;

            #[CommandHandler('notification.send')]
            public function send(
                #[Reference('tenantSettings', 'service.channelName()')] NotificationChannel $channel,
            ): void {
                $this->channel = $channel;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('notification.send');

        self::assertSame(NotificationChannel::Sms, $handler->channel);
    }

    public function test_reference_expression_result_already_of_the_parameter_type_is_passed_as_it_is(): void
    {
        $handler = new class () {
            public ?TenantInvoicing $invoicing = null;

            #[CommandHandler('invoice.issue')]
            public function issue(
                #[Reference('tenantSettings', 'service.invoicing()')] TenantInvoicing $invoicing,
            ): void {
                $this->invoicing = $invoicing;
            }
        };
        $tenantSettings = $this->tenantSettings();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $tenantSettings],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('invoice.issue');

        self::assertSame($tenantSettings->invoicing(), $handler->invoicing);
    }

    public function test_scalar_expression_result_for_a_scalar_parameter_is_passed_as_it_is(): void
    {
        $handler = new class () {
            public ?float $limit = null;

            #[CommandHandler('limit.read')]
            public function read(
                #[Reference('tenantSettings', 'service.limit()')] float $limit,
            ): void {
                $this->limit = $limit;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('limit.read');

        self::assertSame(5.0, $handler->limit);
    }

    public function test_header_expression_result_is_converted_to_the_parameter_type(): void
    {
        $handler = new class () {
            public ?NotificationChannel $channel = null;

            #[CommandHandler('notification.send')]
            public function send(
                #[Header('preferences', "value['channel']")] NotificationChannel $channel,
            ): void {
                $this->channel = $channel;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        $ecotone->sendCommandWithRouting('notification.send', metadata: ['preferences' => ['channel' => 'sms']]);

        self::assertSame(NotificationChannel::Sms, $handler->channel);
    }

    public function test_payload_expression_result_is_converted_to_the_parameter_type(): void
    {
        $handler = new class () {
            public ?NotificationChannel $channel = null;

            #[CommandHandler('notification.send')]
            public function send(
                #[Payload("value['channel']")] NotificationChannel $channel,
            ): void {
                $this->channel = $channel;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        $ecotone->sendCommandWithRouting('notification.send', ['channel' => 'sms']);

        self::assertSame(NotificationChannel::Sms, $handler->channel);
    }

    public function test_expression_result_no_converter_handles_is_refused_naming_the_way_out(): void
    {
        $handler = new class () {
            #[CommandHandler('invoice.issue')]
            public function issue(
                #[Reference('tenantSettings', 'service.invoicingData()')] TenantInvoicing $invoicing,
            ): void {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
        );

        try {
            $ecotone->sendCommandWithRouting('invoice.issue');
            self::fail('Expected the expression result to be refused');
        } catch (MethodInvocationException $exception) {
            self::assertInstanceOf(ExpressionEvaluationException::class, $exception->getPrevious());
            self::assertSame(
                '#[Reference] on $invoicing in ' . $handler::class . '::issue failed.'
                . ' Expression: service.invoicingData().'
                . " The expression returned an array with keys 'tenantId', 'currency', which cannot be passed as " . TenantInvoicing::class . '.'
                . ' Register a #[Converter] from array<string,string> to ' . TenantInvoicing::class . ', return ' . TenantInvoicing::class . ' from the expression, or change the parameter type.',
                $exception->getPrevious()->getMessage(),
            );
        }
    }

    public function test_null_expression_result_for_a_parameter_not_allowing_null_is_refused_naming_the_way_out(): void
    {
        $handler = new class () {
            #[CommandHandler('notification.send')]
            public function send(
                #[Header('preferences', "value['channel']")] NotificationChannel $channel,
            ): void {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        try {
            $ecotone->sendCommandWithRouting('notification.send', metadata: ['preferences' => ['channel' => null]]);
            self::fail('Expected the expression result to be refused');
        } catch (MethodInvocationException $exception) {
            self::assertInstanceOf(ExpressionEvaluationException::class, $exception->getPrevious());
            self::assertSame(
                '#[Header] on $channel in ' . $handler::class . '::send failed.'
                . " Expression: value['channel']."
                . ' The expression returned null, and the parameter does not allow null. Return a value from the expression or declare the parameter nullable.',
                $exception->getPrevious()->getMessage(),
            );
        }
    }

    public function test_expression_result_that_fails_to_convert_names_the_conversion_that_failed(): void
    {
        $handler = new class () {
            #[CommandHandler('notification.send')]
            public function send(
                #[Payload("value['channel']")] NotificationChannel $channel,
            ): void {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        try {
            $ecotone->sendCommandWithRouting('notification.send', ['channel' => 'fax']);
            self::fail('Expected the conversion to fail');
        } catch (MethodInvocationException $exception) {
            self::assertInstanceOf(ExpressionEvaluationException::class, $exception->getPrevious());
            self::assertSame(
                '#[Payload] on $channel in ' . $handler::class . '::send failed.'
                . " Expression: value['channel']."
                . ' Converting the expression result from string to ' . NotificationChannel::class . ' failed: "fax" is not a valid backing value for enum ' . NotificationChannel::class,
                $exception->getPrevious()->getMessage(),
            );
        }
    }

    public function test_array_expression_result_for_a_documented_collection_parameter_is_passed_as_it_is_when_nothing_converts_it(): void
    {
        $handler = new class () {
            public array $invoicings = [];

            /**
             * @param TenantInvoicing[] $invoicings
             */
            #[CommandHandler('invoice.issueAll')]
            public function issueAll(
                #[Reference('tenantSettings', 'service.allInvoicingData()')] array $invoicings,
            ): void {
                $this->invoicings = $invoicings;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('invoice.issueAll');

        self::assertSame([['tenantId' => 'tenant-a', 'currency' => 'eur']], $handler->invoicings);
    }

    public function test_generator_expression_result_reaches_the_handler_unconsumed(): void
    {
        $handler = new class () {
            public array $currencies = [];

            /**
             * @param iterable<string> $currencies
             */
            #[CommandHandler('invoice.issueAll')]
            public function issueAll(
                #[Reference('tenantSettings', 'service.streamedCurrencies()')] iterable $currencies,
            ): void {
                foreach ($currencies as $currency) {
                    $this->currencies[] = $currency;
                }
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('invoice.issueAll');

        self::assertSame(['eur', 'usd'], $handler->currencies);
    }

    public function test_expression_result_is_converted_for_a_query_handler_parameter(): void
    {
        $handler = new class () {
            #[QueryHandler('notification.channel')]
            public function channel(
                #[Header('preferences', "value['channel']")] NotificationChannel $channel,
            ): string {
                return $channel->name;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        self::assertSame('Sms', $ecotone->sendQueryWithRouting('notification.channel', metadata: ['preferences' => ['channel' => 'sms']]));
    }

    public function test_expression_result_is_converted_for_an_asynchronous_handler_parameter(): void
    {
        $handler = new class () {
            public ?NotificationChannel $channel = null;

            #[Asynchronous('notifications')]
            #[CommandHandler('notification.send', endpointId: 'notification.send.endpoint')]
            public function send(
                #[Reference('tenantSettings', 'service.channelName()')] NotificationChannel $channel,
            ): void {
                $this->channel = $channel;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, 'tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('notifications')],
        );

        $ecotone->sendCommandWithRouting('notification.send');
        self::assertNull($handler->channel);

        $ecotone->run('notifications');

        self::assertSame(NotificationChannel::Sms, $handler->channel);
    }

    public function test_expression_result_is_converted_for_an_interceptor_parameter(): void
    {
        $handler = new class () {
            public ?NotificationChannel $interceptedChannel = null;

            #[Before(pointcut: CommandHandler::class)]
            public function capture(
                #[Header('preferences', "value['channel']")] NotificationChannel $channel,
            ): void {
                $this->interceptedChannel = $channel;
            }

            #[CommandHandler('notification.send')]
            public function send(): void
            {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$handler::class], [$handler], enabledModules: []);

        $ecotone->sendCommandWithRouting('notification.send', metadata: ['preferences' => ['channel' => 'sms']]);

        self::assertSame(NotificationChannel::Sms, $handler->interceptedChannel);
    }

    public function test_expression_result_is_converted_for_an_aggregate_handler_parameter(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [NotificationPreference::class],
            ['tenantSettings' => $this->tenantSettings()],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('preference.register', 'customer-1');

        self::assertSame(
            NotificationChannel::Sms,
            $ecotone->sendQueryWithRouting('preference.channel', metadata: ['aggregate.id' => 'customer-1']),
        );
    }

    private function tenantSettings(): object
    {
        return new class () {
            private TenantInvoicing $invoicing;

            public function __construct()
            {
                $this->invoicing = new TenantInvoicing('tenant-a', 'eur');
            }

            public function channelName(): string
            {
                return 'sms';
            }

            public function invoicing(): TenantInvoicing
            {
                return $this->invoicing;
            }

            public function invoicingData(): array
            {
                return ['tenantId' => 'tenant-a', 'currency' => 'eur'];
            }

            public function allInvoicingData(): array
            {
                return [$this->invoicingData()];
            }

            public function streamedCurrencies(): Generator
            {
                yield 'eur';
                yield 'usd';
            }

            public function limit(): int
            {
                return 5;
            }
        };
    }
}

enum NotificationChannel: string
{
    case Sms = 'sms';
}

final class TenantInvoicing
{
    public function __construct(public string $tenantId, public string $currency)
    {
    }
}

#[Aggregate]
final class NotificationPreference
{
    private function __construct(#[Identifier] private string $customerId, private NotificationChannel $channel)
    {
    }

    #[CommandHandler('preference.register')]
    public static function register(
        string $customerId,
        #[Reference('tenantSettings', 'service.channelName()')] NotificationChannel $channel,
    ): self {
        return new self($customerId, $channel);
    }

    #[QueryHandler('preference.channel')]
    public function channel(): NotificationChannel
    {
        return $this->channel;
    }
}
