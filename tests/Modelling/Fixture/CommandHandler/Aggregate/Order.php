<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptors;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\InitializeAggregateOnNotFound;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\Annotation\Version;
use Ecotone\Modelling\WithAggregateEvents;

/**
 * Class Order
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class Order implements VersionAggregate
{
    use WithAggregateEvents;

    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $orderId;
    /**
     * @var int
     */
    private $amount = 0;
    /**
     * @var string
     */
    private $shippingAddress;
    /**
     * @var int
     * @Version()
     */
    private $version;
    /**
     * @var string
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

        $this->record(new Notification());
    }

    /**
     * @param CreateOrderCommand $command
     *
     * @return Order
     * @CommandHandler(
     *     redirectToOnAlreadyExists="increaseAmount"
     * )
     */
    public static function createWith(CreateOrderCommand $command) : self
    {
        $order = new self($command);

        return $order;
    }

    /**
     * @param IncreaseAmountCommand $command
     */
    public function increaseAmount(IncreaseAmountCommand $command) : void
    {
        $this->amount += $command->getAmount();
    }

    /**
     * @param ChangeShippingAddressCommand $command
     * @CommandHandler()
     */
    public function changeShippingAddress(ChangeShippingAddressCommand $command) : void
    {
        $this->shippingAddress = $command->getShippingAddress();

        $this->record(new Notification());
    }


    /**
     * @param MultiplyAmountCommand $command
     * @CommandHandler()
     */
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
     * @return string
     */
    public function getCustomerId(): string
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

    /**
     * @param GetOrderAmountQuery $query
     *
     * @return int
     * @QueryHandler(inputChannelName="get_order_amount_channel")
     */
    public function getAmountWithQuery(GetOrderAmountQuery $query) : int
    {
        return $this->amount;
    }

    public function hasVersion(int $version) : bool
    {
        return $this->version == $version;
    }

    /**
     * @return string
     * @QueryHandler(endpointId="getShipping", inputChannelName="getShippingChannel", messageClassName=GetShippingAddressQuery::class)
     */
    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    /**
     * @QueryHandler(inputChannelName="getVersion", ignoreMessage=true)
     */
    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function increaseAggregateVersion() : void
    {
        $this->version += 1;
    }
}