Feature: activating as aggregate order entity

#  Scenario: I order product and change shipping address
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate"
#    And I have order with id 1 for 20 products registered to shipping address "London 12th street"
#    Then there should notification 1 awaiting notification
#    And shipping address should be "London 12th street" for order with id 1
#    When I change order with id of 1 the shipping address to "London 13th street"
#    Then shipping address should be "London 13th street" for order with id 1
#    And there should notification 2 awaiting notification
#    And there should be 20 products for order with id 1 retrieved from "get_order_amount_channel"

#  Scenario: I reorder product, then the amount should be increased
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate"
#    When I have order with id 1 for 20 products registered to shipping address "London 12th street"
#    And I have order with id 1 for 30 products registered to shipping address "London 52th street"
#    Then there should be 50 products for order with id 1 retrieved from "get_order_amount_channel"

#  Scenario: I rent appointment and expect to
#  Calculations are combined with interceptors.
#  This will focus on asynchronous scenario using inbound channel adapter
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\Renter"
#    When I rent appointment with id 123 and duration 100
#    Then calendar should contain event with appointment id 123

#  Scenario: I order product and I want to see it on the list of orders products using service
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\Order"
#    When I order product "milk"
#    Then there should be nothing on the order list
#    When I active receiver "orders"
#    Then on the order list I should see "milk"
#    And notification list should be empty
#    When I active receiver "orders"
#    Then on notification list I should see "milk"

#  Scenario: I order product and I want to see it on the list of orders products using aggregate
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\OrderAggregate"
#    When I order product "milk"
#    Then there should be no "milk" order
#    When I active receiver "orders"
#    Then there should be "milk" order
#    And no notification for "milk"
#    When I active receiver "orders"
#    Then there should be notification about "milk" 1 time
#    Then logs count be 0
#    When I active receiver "orders"
#    Then logs count be 1

#  Scenario: Calculating price in specific shop. Make use of before, after interceptors and output channel for aggregate query handlers
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate"
#    When I register shop with margin 20
##    (milk price (100) + shop margin (20) + franchise margin (10)) * vat (2.0)
#    Then for "milk" product there should be price of 260

#  Scenario: Storing logs. Make use of before, after interceptors and output channel for aggregate command handlers
#    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate"
#    When current time is "2020-02-02 12:00:00"
#    And current user is "Johny"
#    And I send log with information "User logged in"
#    Then there should be log for "User logged in" at time "2020-02-02 12:00:00" and user "Johny"
#    When current time is "2020-02-02 12:10:00"
#    And I send log with information "Another User logged in"
#    Then there should be log for "Another User logged in" at time "2020-02-02 12:10:00" and user "Johny"

  Scenario: Storing logs. Make use of around interceptor for aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate"
    And current time is "2020-02-02 12:00:00"
    And current user is "Johny"
    And I send log with information "User logged in"
    Then current user is "Franco"
    When I send log with information "Another User logged in" I should be disallowed