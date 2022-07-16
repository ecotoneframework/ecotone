<?php

namespace Test\Ecotone\EventSourcing\Fixture\Basket;

use Ecotone\EventSourcing\Attribute\AggregateType;
use Ecotone\EventSourcing\Attribute\Stream;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Ecotone\Modelling\WithAggregateVersioning;
use Test\Ecotone\EventSourcing\Fixture\Basket\Command\AddProduct;
use Test\Ecotone\EventSourcing\Fixture\Basket\Command\CreateBasket;
use Test\Ecotone\EventSourcing\Fixture\Basket\Event\BasketWasCreated;
use Test\Ecotone\EventSourcing\Fixture\Basket\Event\ProductWasAddedToBasket;

#[EventSourcingAggregate(true)]
#[Stream(self::BASKET_STREAM)]
#[AggregateType(self::AGGREGATE_TYPE)]
class Basket
{
    use WithAggregateEvents;
    use WithAggregateVersioning;
    public const BASKET_STREAM = 'basket_stream';
    public const AGGREGATE_TYPE = 'basket';

    #[AggregateIdentifier]
    private string $id;

    private array $currentBasket = [];

    #[CommandHandler]
    public static function create(CreateBasket $command): static
    {
        $basket = new static();
        $basket->recordThat(new BasketWasCreated($command->getId()));

        return $basket;
    }

    #[CommandHandler]
    public function addProduct(AddProduct $command): void
    {
        $this->recordThat(new ProductWasAddedToBasket($this->id, $command->getProductName()));
    }

    #[EventSourcingHandler]
    public function whenBasketWasCreated(BasketWasCreated $event): void
    {
        $this->id = $event->getId();
    }

    #[EventSourcingHandler]
    public function whenProductWasAdded(ProductWasAddedToBasket $event): void
    {
        $this->currentBasket[] = $event->getProductName();
    }

    #[QueryHandler('basket.getCurrent')]
    public function getCurrentBasket(): array
    {
        return $this->currentBasket;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'currentBasket' => $this->currentBasket,
            'version' => $this->version,
        ];
    }

    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->id = $data['id'];
        $self->currentBasket = $data['currentBasket'];
        $self->version = $data['version'];

        return $self;
    }
}
