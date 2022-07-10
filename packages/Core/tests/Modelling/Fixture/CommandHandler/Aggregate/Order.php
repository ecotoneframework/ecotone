<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\IgnorePayload;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\WithAggregateEvents;

#[Aggregate]
class Order implements VersionAggregate
{
    use WithAggregateEvents;

    #[AggregateIdentifier]
    private string $orderId;
    /**
     * @var int
     */
    private $amount = 0;
    /**
     * @var string
     */
    private $shippingAddress;
    #[AggregateVersion]
    private $version;
    /**
     * @var string|null
     */
    private $customerId;

    /**
     * Order constructor.
     *
     * @param CreateOrderCommand $createOrderCommand
     */
    private function __construct(CreateOrderCommand $createOrderCommand)
    {
        $this->orderId = $createOrderCommand->getOrderId();
        $this->amount = $createOrderCommand->getAmount();
        $this->shippingAddress = $createOrderCommand->getShippingAddress();

        $this->recordThat(new Notification());
    }

    #[CommandHandler]
    public static function createWith(CreateOrderCommand $command) : self
    {
        return new self($command);
    }

    #[CommandHandler]
    public function increaseAmount(CreateOrderCommand $command) : void
    {
        $this->amount += $command->getAmount();
    }
    
    public function increaseAmountUsingDifferentClass(IncreaseAmountCommand $command) : void
    {
        $this->amount += $command->getAmount();
    }

    #[CommandHandler]
    public function changeShippingAddress(ChangeShippingAddressCommand $command) : void
    {
        $this->shippingAddress = $command->getShippingAddress();

        $this->recordThat(new Notification());
    }


    #[CommandHandler]
    public function multiplyOrder(MultiplyAmountCommand $command) : void
    {
        $this->amount *= $command->getAmount();
    }

    /**
     * @param FinishOrderCommand $command
     * @param string $customerId
     */
    public function finish(FinishOrderCommand $command, string $customerId) : void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    #[QueryHandler("get_order_amount_channel")]
    public function getAmountWithQuery(GetOrderAmountQuery $query) : int
    {
        return $this->amount;
    }

    public function hasVersion(int $version) : bool
    {
        return $this->version == $version;
    }

    #[QueryHandler(endpointId: "getShipping")]
    public function getShippingAddress(GetShippingAddressQuery $query): string
    {
        return $this->shippingAddress;
    }

    #[QueryHandler("getVersion")]
    #[IgnorePayload]
    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function increaseAggregateVersion() : void
    {
        $this->version += 1;
    }
}