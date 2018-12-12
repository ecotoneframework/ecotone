Feature: activating as aggregate order entity


  Scenario: I order product and change shipping address
    Given I have order with id 1 for 20 products registered to shipping address "London 12th street"
    Then shipping address should be "London 12th street" for order with id 1
    When I change order with id of 1 the shipping address to "London 13th street"
    Then shipping address should be "London 13th street" for order with id 1
    Then there should be 20 products for order with id 1 retrieved from "get_order_amount_channel"