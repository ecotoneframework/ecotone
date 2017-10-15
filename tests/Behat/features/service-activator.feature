Feature: As a user of the system I want to manage flow

  Scenario: As system operator I want to register client bookings
    Given I activate service "Fixture\Service\ServiceWithoutReturnValue" with method "setName" to listen on "bookingConfirmationRequest" channel
    When message with payload "I reserve seat" comes to "bookingConfirmationRequest" channel
    Then booking request should be processed