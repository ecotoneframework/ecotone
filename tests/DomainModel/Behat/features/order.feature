Feature: activating as aggregate order entity


  Scenario: I order product and change shipping address
    Given I configure messaging system for modelling
    And I have order with id 1 for 20 products registered to shipping address "London 12th street"
    Then there should notification 1 awaiting notification
    And shipping address should be "London 12th street" for order with id 1
    When I change order with id of 1 the shipping address to "London 13th street"
    Then shipping address should be "London 13th street" for order with id 1
    Then there should be 20 products for order with id 1 retrieved from "get_order_amount_channel"

  Scenario: I reorder product, then the amount should be increased
    Given I configure messaging system for modelling
    When I have order with id 1 for 20 products registered to shipping address "London 12th street"
    And I have order with id 1 for 30 products registered to shipping address "London 52th street"
    Then there should be 50 products for order with id 1 retrieved from "get_order_amount_channel"