# This is Read Only Repository
To contribute make use of [Ecotone-Dev repository](https://github.com/ecotoneframework/ecotone-dev).

<p align="left"><a href="https://ecotone.tech" target="_blank">
    <img src="https://github.com/ecotoneframework/ecotone-dev/blob/main/ecotone_small.png?raw=true">
</a></p>

![Github Actions](https://github.com/ecotoneFramework/ecotone-dev/actions/workflows/split-testing.yml/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/ecotone/ecotone/v/stable)](https://packagist.org/packages/ecotone/ecotone)
[![License](https://poser.pugx.org/ecotone/ecotone/license)](https://packagist.org/packages/ecotone/ecotone)
[![Total Downloads](https://img.shields.io/packagist/dt/ecotone/ecotone)](https://packagist.org/packages/ecotone/ecotone)
[![PHP Version Require](https://img.shields.io/packagist/dependency-v/ecotone/ecotone/php.svg)](https://packagist.org/packages/ecotone/ecotone)

# Ecotone — the enterprise architecture layer for Laravel and Symfony

One Composer package adds CQRS, Event Sourcing, Sagas, Projections, Workflows, and Outbox messaging to your existing application — all via declarative PHP 8 attributes on the classes you already have. No base classes, no bus wiring, no retry config. The same patterns proven in Java's Spring/Axon and .NET's NServiceBus/MassTransit, brought to PHP without giving up the development speed PHP is known for.

Visit [ecotone.tech](https://ecotone.tech) for the full overview.

---

## See what it looks like

```php
class OrderService
{
    #[CommandHandler]
    public function placeOrder(PlaceOrder $command, EventBus $eventBus): void
    {
        // your business logic
        $eventBus->publish(new OrderWasPlaced($command->orderId));
    }

    #[QueryHandler('order.getStatus')]
    public function getStatus(string $orderId): string
    {
        return $this->orders[$orderId]->status;
    }
}

class NotificationService
{
    #[Asynchronous('notifications')]
    #[EventHandler]
    public function whenOrderPlaced(OrderWasPlaced $event, NotificationSender $sender): void
    {
        $sender->sendOrderConfirmation($event->orderId);
    }
}
```

**That's the entire setup.** No bus configuration. No handler registration. No retry config. No serialization wiring. Ecotone reads your attributes and handles the rest:

- **Command, Query, and Event Bus** — wired automatically from your attributes
- **Event routing** — handlers subscribe to events without manual wiring
- **Async execution** — `#[Asynchronous('notifications')]` routes to RabbitMQ, SQS, Kafka, Redis, or DBAL
- **Failure isolation** — each event handler gets its own copy of the message, so one handler's failure never blocks another
- **Retries and dead letter** — failed messages retry automatically; permanently failed ones go to a dead letter queue you can inspect and replay
- **Tracing** — OpenTelemetry traces every message across sync and async flows

---

## Test exactly the flow you care about

Extract a specific flow and test it in isolation — only the services you need:

```php
$ecotone = EcotoneLite::bootstrapFlowTesting([OrderService::class]);

$ecotone->sendCommand(new PlaceOrder('order-1'));

$this->assertEquals('placed', $ecotone->sendQueryWithRouting('order.getStatus', 'order-1'));
```

Only `OrderService` is loaded — no notifications, no other handlers. Just the flow you're verifying.

Now bring in the full async flow. Enable an in-memory channel and run it inside the same test process:

```php
$notifier = new InMemoryNotificationSender();

$ecotone = EcotoneLite::bootstrapFlowTesting(
    [OrderService::class, NotificationService::class],
    [NotificationSender::class => $notifier],
    enableAsynchronousProcessing: [
        SimpleMessageChannelBuilder::createQueueChannel('notifications')
    ]
);

$ecotone
    ->sendCommand(new PlaceOrder('order-1'))
    ->run('notifications');

$this->assertEquals(['order-1'], $notifier->getSentOrderConfirmations());
```

Swap the in-memory channel for DBAL, RabbitMQ, or Kafka in production — the test stays the same. Ecotone runs the consumer in-process, so switching transports never changes how you test.

---

## What's in the box

| Area | What you get |
|---|---|
| **Messaging** | Command / Query / Event buses, routing, interceptors, business interfaces (gateways) |
| **Domain modelling** | Aggregates, Sagas, state-stored or event-sourced — all via attributes |
| **Event Sourcing** | Event Store, Projections (catch-up, partitioned, streaming), event versioning and upcasting, DCB |
| **Workflows** | Stateless workflows, orchestrators (routing slip), saga-based process managers |
| **Async & resiliency** | `#[Asynchronous]`, retries, error channels, dead letter queue with replay, Outbox pattern |
| **Observability** | OpenTelemetry tracing, Service Map (Enterprise) |
| **Multi-tenancy** | Per-tenant connections, event stores, and async channels |
| **Distribution** | Distributed Bus for cross-service events and commands |
| **Data protection** | Field-level encryption and PII masking for messages |

---

## Install for your framework

Ecotone is not a replacement for Symfony Messenger or Laravel Queue — it's the architecture layer on top. Your existing HTTP layer, transports, and jobs keep working.

**Symfony**
```bash
composer require ecotone/symfony-bundle
```
Symfony Messenger compatible. Bundle auto-configuration. Doctrine ORM integration. Pure POPOs. → [Symfony guide](https://docs.ecotone.tech/modules/symfony-ddd-cqrs-event-sourcing)

**Laravel**
```bash
composer require ecotone/laravel
```
Works with Eloquent and Doctrine. Laravel Queue integration. Auto-discovery, zero config. → [Laravel guide](https://docs.ecotone.tech/modules/laravel-ddd-cqrs-event-sourcing)

**Any PSR-11 framework (Ecotone Lite)**
```bash
composer require ecotone/ecotone
```
Full feature set. No framework lock-in. → [Ecotone Lite guide](https://docs.ecotone.tech/install-php-service-bus#install-ecotone-lite-no-framework)

---

## AI-Ready by design

Declarative attributes mean less infrastructure code for your coding agent to read and less boilerplate for it to generate — smaller context, faster iteration, more accurate results.

- **MCP Server**: `https://docs.ecotone.tech/~gitbook/mcp` — [Install in VSCode](vscode:mcp/install?%7B%22name%22%3A%22Ecotone%22%2C%22url%22%3A%22https%3A%2F%2Fdocs.ecotone.tech%2F~gitbook%2Fmcp%22%7D)
- **Agentic Skills** — ready-to-use skills that teach any coding agent to correctly write handlers, aggregates, sagas, projections, and tests
- **LLMs.txt**: [ecotone.tech/llms.txt](https://ecotone.tech/llms.txt)
- **Context7**: Available via [@upstash/context7-mcp](https://github.com/upstash/context7)

Learn more: [AI Integration Guide](https://docs.ecotone.tech/other/ai-integration)

---

## Getting started

See the [quickstart guide](https://docs.ecotone.tech/quick-start), the [full documentation](https://docs.ecotone.tech), and the [Ecotone Blog](https://blog.ecotone.tech).

Prefer runnable code? The [quickstart examples](https://github.com/ecotoneframework/quickstart-examples) cover handlers, aggregates, sagas, event sourcing, projections, outbox, multi-tenancy, and more — each running end-to-end in seconds.

## Feature requests and issue reporting

Use [issue tracking system](https://github.com/ecotoneframework/ecotone-dev/issues) for new feature request and bugs.
Please verify that it's not already reported by someone else.

## Contact

If you want to talk or ask questions about Ecotone

- [**Twitter**](https://twitter.com/EcotonePHP)
- **support@simplycodedsoftware.com**
- [**Community Channel**](https://discord.gg/GwM2BSuXeg)

## Support Ecotone

If you want to help building and improving Ecotone consider becoming a sponsor:

- [Sponsor Ecotone](https://github.com/sponsors/dgafka)
- [Contribute to Ecotone](https://github.com/ecotoneframework/ecotone-dev).

## Tags

PHP, DDD, CQRS, Event Sourcing, Sagas, Projections, Workflows, Outbox, Symfony, Laravel, Service Bus, Event Driven Architecture
