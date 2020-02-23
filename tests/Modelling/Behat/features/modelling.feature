Feature: activating as aggregate order entity

  Scenario: I order product and change shipping address
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate"
    And I have order with id 1 for 20 products registered to shipping address "London 12th street"
    Then there should notification 1 awaiting notification
    And shipping address should be "London 12th street" for order with id 1
    When I change order with id of 1 the shipping address to "London 13th street"
    Then shipping address should be "London 13th street" for order with id 1
    And there should notification 2 awaiting notification
    And there should be 20 products for order with id 1 retrieved from "get_order_amount_channel"

  Scenario: I reorder product, then the amount should be increased
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate"
    When I have order with id 1 for 20 products registered to shipping address "London 12th street"
    And I have order with id 1 for 30 products registered to shipping address "London 52th street"
    Then there should be 50 products for order with id 1 retrieved from "get_order_amount_channel"

  Scenario: I rent appointment and expect to
  Calculations are combined with interceptors.
  This will focus on asynchronous scenario using inbound channel adapter
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\Renter"
    When I rent appointment with id 123 and duration 100
    Then calendar should contain event with appointment id 123

  Scenario: I order product and I want to see it on the list of orders products
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\Order"
    When I order product "milk"
    Then there should be nothing on the order list
    When I active receiver "orders"
    Then on the order list I should see "milk"

  Scenario: I order product and I want to see it on the list of orders products
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\OrderAggregate"
    When I order product "milk"
    Then there should be no "milk" order
    When I active receiver "orders"
    Then there should be "milk" order