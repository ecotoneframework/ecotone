Feature: activating as aggregate order entity

  Scenario: I order product and I want to see it on the list of orders products
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\Order"
    When I order "milk"
    Then there should be nothing on the order list
    When I active receiver "orders"
    Then on the order list I should see "milk"

  Scenario: I order with transaction a product with failure, so the order should never be placed
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\FailureTransaction"
    When I transactionally order "milk"
    When I transactionally order "milk"
    When I active receiver "placeOrder"
    And there should be no next order

  Scenario: I order with transaction a product with fatal error, so the order should never be placed
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError"
    When I transactionally order "milk"
    When I active receiver "placeOrder"
    When I active receiver "placeOrder"
    And there should be no next order

  Scenario: I order with transaction a product with success, so the order should be placed
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\SuccessTransaction"
    When I transactionally order "milk"
    And I active receiver "placeOrderEndpoint"
    Then there should be "milk" order
    And I active receiver "placeOrderEndpoint"
    And there should be no next order

  Scenario: I add product to shopping cart with publisher and consume it
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\Shop"
    When I add product "window" to shopping cart
    And I active receiver "addToCart"
    Then there should be product "window" in shopping cart

  Scenario: Application exception handling with retries
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\ErrorChannel"
    When I order "coffee"
    And I call consumer "correctOrders"
    Then there should be 0 orders
    And I call consumer "correctOrders"
    Then there should be 0 orders
    And I call consumer "correctOrders"
    Then there should be 1 orders

  Scenario: Application exception handling with retries and dead letter
    Given I active messaging for namespace "Test\Ecotone\Amqp\Fixture\DeadLetter"
    When I order "coffee"
    And I call consumer "correctOrders"
    And I call consumer "incorrectOrdersEndpoint"
    Then there should be 0 orders
    Then there should be 0 incorrect orders
    And I call consumer "correctOrders"
    And I call consumer "incorrectOrdersEndpoint"
    Then there should be 0 orders
    Then there should be 0 incorrect orders
    And I call consumer "correctOrders"
    And I call consumer "incorrectOrdersEndpoint"
    Then there should be 0 orders
    Then there should be 1 incorrect orders

#    @TODO
#  Scenario: Distributing command to another service
#    Given I active messaging distributed services:
#      | name            | namespace |
#      | user_service    | Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Publisher |
#      | ticket_service  | Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver  |
#    When using "ticket_service" I should have 0 remaining ticket
#    And using "user_service" I change billing details
#    Then using "ticket_service" I should have 1 remaining ticket

#    @TODO
#  Scenario: Distributing event to another service
#    Given I active messaging distributed services:
#      | name            | namespace |
#      | user_service    | Test\Ecotone\Amqp\Fixture\DistributedEventBus\Publisher |
#      | ticket_service  | Test\Ecotone\Amqp\Fixture\DistributedEventBus\Receiver  |
#    When using "ticket_service" I should have 0 remaining ticket
#    And using "user_service" I change billing details
#    Then using "ticket_service" I should have 1 remaining ticket

    Scenario: Distributing command to another service
    Given I active messaging distributed services:
      | name            | namespace |
      | user_service    | Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Publisher |
      | ticket_service  | Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver  |
    When using "ticket_service" I should have 0 remaining ticket
    And using "user_service" I change billing details
    Then using "ticket_service" I should have 1 remaining ticket

  Scenario: Application exception handling with retry dead letter when using distribution
    Given I active messaging distributed services:
      | name            | namespace |
      | user_service    | Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Publisher |
      | ticket_service  | Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver  |
    When using "ticket_service" there are 0 error tickets
    And using "user_service" I change billing details
    And using "ticket_service" process ticket with failure
    Then using "ticket_service" there are 0 error tickets
    Then using "ticket_service" process ticket with failure
    Then using "ticket_service" there are 1 error tickets