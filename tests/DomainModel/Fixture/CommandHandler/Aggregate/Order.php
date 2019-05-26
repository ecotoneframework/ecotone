<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\DomainModel\Annotation\InitializeAggregateOnNotFound;
use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptors;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\AggregateIdentifier;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;

/**
 * Class Order
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class Order implements VersionAggregate
{
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
     */
    private $version = 0;
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

        $this->increaseAggregateVersion();
    }

    /**
     * @param CreateOrderCommand $command
     * @param EventBus           $eventBus
     *
     * @return Order
     * @CommandHandler(
     *     redirectToOnAlreadyExists="increaseAmount"
     * )
     */
    public static function createWith(CreateOrderCommand $command, EventBus $eventBus) : self
    {
        $order = new self($command);

        $eventBus->send(new Notification());
        return $order;
    }

    /**
     * @param IncreaseAmountCommand $command
     * @param EventBus $eventBus
     */
    public function increaseAmount(IncreaseAmountCommand $command, EventBus $eventBus) : void
    {
        $this->amount += $command->getAmount();
        $this->increaseAggregateVersion();
    }

    /**
     * @param ChangeShippingAddressCommand $command
     * @CommandHandler()
     */
    public function changeShippingAddress(ChangeShippingAddressCommand $command) : void
    {
        $this->shippingAddress = $command->getShippingAddress();
        $this->increaseAggregateVersion();
    }


    /**
     * @param MultiplyAmountCommand $command
     * @CommandHandler()
     */
    public function multiplyOrder(MultiplyAmountCommand $command) : void
    {
        $this->amount *= $command->getAmount();
        $this->increaseAggregateVersion();
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
     * @inheritDoc
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    private function increaseAggregateVersion() : void
    {
        $this->version += 1;
    }
}