<?php

namespace Test\SimplyCodedSoftware\DomainModel\Behat\Bootstrap;

require_once __DIR__ . "/../../../TestBootstrap.php";

use Behat\Behat\Context\Context;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\InMemoryOrderRepositoryFactory;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\Order;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Conversion\DeserializingConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\DomainModel\CommandGateway;
use SimplyCodedSoftware\DomainModel\Config\AggregateMessageRouterModule;
use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
use SimplyCodedSoftware\DomainModel\MessageGateway;
use SimplyCodedSoftware\DomainModel\QueryGateway;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Defines application features from the specific context.
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @var QueryGateway
     */
    private $queryGateway;
    /**
     * @var CommandGateway
     */
    private $commandGateway;
    /**
     * @var MessageGateway
     */
    private $messageGateway;

    /**
     * @Given I have order with id :orderId for :productAmount products registered to shipping address :shippingAddress
     *
     * @param int    $orderId
     * @param int    $productAmount
     * @param string $shippAddress
     */
    public function iHaveOrderWithIdForProductsRegisteredToShippingAddress(int $orderId, int $productAmount, string $shippAddress)
    {
        $this->commandGateway->execute(CreateOrderCommand::create($orderId, $productAmount, $shippAddress));
    }

    /**
     * @When I change order with id of :orderId the shipping address to :shippingAddress
     *
     * @param int    $orderId
     * @param string $shippAddress
     */
    public function iChangeOrderWithIdOfTheShippingAddressTo(int $orderId, string $shippAddress)
    {
        $this->commandGateway->execute(ChangeShippingAddressCommand::create($orderId, 0, $shippAddress));
    }

    /**
     * @Then shipping address should be :shippingAddress for order with id :orderId
     *
     * @param string $shippingAddress
     * @param int    $orderId
     */
    public function shippingAddressShouldBeForOrderWithId(string $shippingAddress, int $orderId)
    {
        $execute = $this->queryGateway->execute(GetShippingAddressQuery::create($orderId));
        $this->assertEquals($shippingAddress, $execute);
    }



    /**
     * @BeforeScenario
     */
    public function setUpMessaging()
    {
        $this->prepareConfiguration([Order::class]);
    }

    /**
     * @Then there should be :productsAmount products for order with id :orderId retrieved from :channelName
     * @param int $productsAmount
     * @param int $orderId
     * @param string $channelName
     */
    public function thereShouldBeProductsForOrderWithIdRetrievedFrom(int $productsAmount, int $orderId, string $channelName)
    {
        $executeWithContentType = $this->messageGateway->executeWithContentType($channelName, serialize(GetOrderAmountQuery::createWith($orderId)),MediaType::APPLICATION_X_PHP_SERIALIZED_OBJECT);
        $this->assertEquals(
            $productsAmount,
            $executeWithContentType
        );
    }

    /**
     * @param array $annotationClassesToRegister
     *
     * @return void
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function prepareConfiguration(array $annotationClassesToRegister): void
    {
        $aggregateMessagingModule = AggregateMessagingModule::create(InMemoryAnnotationRegistrationService::createFrom($annotationClassesToRegister));
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([CommandGateway::class, QueryGateway::class, MessageGateway::class, Order::class]);
        $gatewayModule = GatewayModule::create($annotationRegistrationService);
        $aggregateRouterModule = AggregateMessageRouterModule::create($annotationRegistrationService);

        $configuredMessagingSystem = MessagingSystemConfiguration::prepare(
            InMemoryModuleMessaging::createWith(
                [$aggregateMessagingModule, $gatewayModule, $aggregateRouterModule],
                [InMemoryOrderRepositoryFactory::createEmpty()]
            )
        )
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConverter(new DeserializingConverterBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->commandGateway = $configuredMessagingSystem->getGatewayByName(CommandGateway::class);
        $this->queryGateway   = $configuredMessagingSystem->getGatewayByName(QueryGateway::class);
        $this->messageGateway = $configuredMessagingSystem->getGatewayByName(MessageGateway::class);
    }
}
