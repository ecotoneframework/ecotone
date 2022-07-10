Feature: Handle messaging

  Scenario: As a driver I drive car
    Given there is car
    When I speed up to 100
    Then there speed should be 100