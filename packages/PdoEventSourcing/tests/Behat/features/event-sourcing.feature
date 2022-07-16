Feature: activating as aggregate order entity

  Scenario: I verify building synchronous event driven projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection                      |
    And I initialize projection "inProgressTicketList"
    And I should see tickets in progress:
      | ticket_id  | ticket_type    |
    When I register "alert" ticket 123 with assignation to "Johny"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    When I close ticket with id 123
    And I register "info" ticket 124 with assignation to "Johny"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 124        | info          |

  Scenario: I verify building projection from event sourced when snapshots are enabled
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection                      |
      | Test\Ecotone\EventSourcing\Fixture\Snapshots                      |
    And I initialize projection "inProgressTicketList"
    And I should see tickets in progress:
      | ticket_id  | ticket_type    |
    When I register "alert" ticket 123 with assignation to "Johny"
    And I change assignation to "Franco" for ticket 123
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    When I close ticket with id 123
    And I register "info" ticket 124 with assignation to "Johny"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 124        | info          |

  Scenario: I verify building synchronous event driven projection using in memory event store
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection                      |
      | Test\Ecotone\EventSourcing\Fixture\InMemoryEventStore                      |
    When I register "alert" ticket 123 with assignation to "Johny"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    When I close ticket with id 123
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |

  Scenario: I verify building polling projection
    Given I active messaging for namespaces
        | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
        | Test\Ecotone\EventSourcing\Fixture\TicketWithPollingProjection |
    When I register "alert" ticket 123 with assignation to "Johny"
    When I run endpoint with name "inProgressTicketList"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    When I close ticket with id 123
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    And I run endpoint with name "inProgressTicketList"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |

  Scenario: I verify building asynchronous event driven projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithAsynchronousEventDrivenProjection |
    When I register "alert" ticket 123 with assignation to "Johny"
    When I run endpoint with name "asynchronous_projections"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    When I close ticket with id 123
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 123        | alert          |
    And I run endpoint with name "asynchronous_projections"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |

  Scenario: Operations on the polling projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithPollingProjection |
    When I register "alert" ticket 1234 with assignation to "Marcus"
    And I run endpoint with name "inProgressTicketList"
    And I stop the projection for in progress tickets
    And I run endpoint with name "inProgressTicketList"
    And I register "alert" ticket 12345 with assignation to "Andrew"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
    When I reset the projection for in progress tickets
    And I run endpoint with name "inProgressTicketList"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
      | 12345      | alert          |
    And I delete projection for all in progress tickets
    And I run endpoint with name "inProgressTicketList"
    Then there should be no in progress ticket list

  Scenario: Operations on the synchronous event-driven projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection                      |
    When I register "alert" ticket 1234 with assignation to "Marcus"
    And I stop the projection for in progress tickets
    And I register "alert" ticket 12345 with assignation to "Andrew"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
      | 12345      | alert          |
    When I reset the projection for in progress tickets
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
      | 12345      | alert          |
    And I delete projection for all in progress tickets
    Then there should be no in progress ticket list

  Scenario: Operations on asynchronous event-driven projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithAsynchronousEventDrivenProjection |
    When I register "alert" ticket 1234 with assignation to "Marcus"
    And I run endpoint with name "asynchronous_projections"
    And I stop the projection for in progress tickets
    And I run endpoint with name "asynchronous_projections"
    And I register "alert" ticket 12345 with assignation to "Andrew"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
    When I reset the projection for in progress tickets
    And I run endpoint with name "asynchronous_projections"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
      | 12345      | alert          |
    And I delete projection for all in progress tickets
#    And I run endpoint with name "asynchronous_projections"
#    Then there should be no in progress ticket list

  Scenario: Catching up events after reset the synchronous event-driven projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithLimitedLoad                      |
    When I register "alert" ticket 1234 with assignation to "Marcus"
    And I register "alert" ticket 1235 with assignation to "Andrew"
    And I register "alert" ticket 1236 with assignation to "Andrew"
    When I reset the projection for in progress tickets
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
      | 1235      | alert          |
      | 1236      | alert          |

  Scenario: Catching up events after reset the asynchronous event-driven projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithAsynchronousEventDrivenProjection |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithLimitedLoad                      |
    When I register "alert" ticket 1234 with assignation to "Marcus"
    And I register "alert" ticket 1235 with assignation to "Andrew"
    And I register "alert" ticket 1236 with assignation to "Andrew"
    And I run endpoint with name "asynchronous_projections"
    And I reset the projection for in progress tickets
    And I run endpoint with name "asynchronous_projections"
    Then I should see tickets in progress:
      | ticket_id  | ticket_type    |
      | 1234       | alert          |
      | 1235      | alert          |
      | 1236      | alert          |

  Scenario: I verify building projection from event sourced aggregate using custom stream name and simple arrays in projections
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Basket                      |
      | Test\Ecotone\EventSourcing\Fixture\BasketListProjection                      |
    When I create basket with id 1000
    And I run endpoint with name "basketList"
    Then I should see baskets:
      | id    | products    |
      | 1000  | []          |
    When I add product "milk" to basket with id 1000
    Then I should see baskets:
      | id    | products    |
      | 1000  | ["milk"]    |

  Scenario: I verify snapshoting aggregates called in turn
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Basket                      |
      | Test\Ecotone\EventSourcing\Fixture\Snapshots                      |
    When I create basket with id 1000
    And I create basket with id 1001
    When I add product "milk" to basket with id 1000
    And I add product "cheese" to basket with id 1001
    And I add product "ham" to basket with id 1000
    And I add product "cheese" to basket with id 1001
    And I add product "milk" to basket with id 1001
    Then basket with id 1000 should contains "milk,ham"
    Then basket with id 1001 should contains "cheese,cheese,milk"

  Scenario: Verify handling multiple streams for projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\ProjectionFromMultipleStreams                      |
      | Test\Ecotone\EventSourcing\Fixture\Basket                      |
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
    When I create basket with id 1000
    And I register "alert" ticket 1234 with assignation to "Marcus"
    Then the result of calling "action_collector.getCount" should be 2

  Scenario: Verify handling specific event stream when stream per aggregate persistence is enabled
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\SpecificEventStream                      |
      | Test\Ecotone\EventSourcing\Fixture\Basket                      |
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
    When I create basket with id 1000
    And I create basket with id 1001
    Then the result of calling "action_collector.getCount" should be 1

  Scenario: Verify handling category stream when stream per aggregate persistence is enabled
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\ProjectionFromCategoryUsingAggregatePerStream                      |
      | Test\Ecotone\EventSourcing\Fixture\Basket                      |
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
    When I create basket with id 1000
    And I create basket with id 1001
    Then the result of calling "action_collector.getCount" should be 2

  Scenario: Verify handling custom event stream when custom stream persistence is enabled
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\CustomEventStream                      |
      | Test\Ecotone\EventSourcing\Fixture\Basket                      |
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
    When I create basket with id 2000
    And I create basket with id 2001
    Then the result of calling "action_collector.getCount" should be 2

  Scenario: Handle event and commands with Value Object Identifiers
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\ValueObjectIdentifier                      |
    When I publish article with id "fc6023e7-1d48-4f59-abc9-72a087787d3e" and content "Good book"
    Then I article with id "fc6023e7-1d48-4f59-abc9-72a087787d3e" should contains "Good book"

  Scenario: Projection emitting events
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection                      |
    And I initialize projection "inProgressTicketList"
    When I register "alert" ticket 123 with assignation to "Johny"
    Then I should be notified with updated tickets "123" and published events count of 1
    When I register "info" ticket 124 with assignation to "Johny"
    Then I should be notified with updated tickets "124" and published events count of 2
    When I close ticket with id 123
    Then I should be notified with updated tickets "123" and published events count of 3

  Scenario: When projection is deleted emitted events will be removed too
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection                      |
    And I initialize projection "inProgressTicketList"
    And I register "alert" ticket 123 with assignation to "Johny"
    And I close ticket with id 123
    When I delete projection for all in progress tickets
    Then there should no notified event

  Scenario: Projection emitting events should not republished in case replaying projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketWithLimitedLoad                      |
    And I initialize projection "inProgressTicketList"
    And I register "alert" ticket 123 with assignation to "Johny"
    And I register "info" ticket 124 with assignation to "Johny"
    When I reset the projection for in progress tickets
    Then I should be notified with updated tickets "124" and published events count of 2

  Scenario: Projection should be able to keep the state between runs
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketProjectionState                      |
    When I register "alert" ticket 123 with assignation to "Johny"
    Then I should see ticket count equal 1 and ticket closed count equal 0
    When I register "alert" ticket 1234 with assignation to "Johny"
    And I close ticket with id 1234
    Then I should see ticket count equal 2 and ticket closed count equal 1
    When I close ticket with id 123
    Then I should see ticket count equal 2 and ticket closed count equal 2

  Scenario: Projection state should be reset together with projection
    Given I active messaging for namespaces
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketProjectionState                      |
    And I register "alert" ticket 123 with assignation to "Johny"
    And I register "alert" ticket 1234 with assignation to "Johny"
    And I close ticket with id 1234
    And I close ticket with id 123
    When I reset the projection "ticketCounter"
    Then I should see ticket count equal 2 and ticket closed count equal 2
    When I register "alert" ticket 12345 with assignation to "Johny"
    Then I should see ticket count equal 3 and ticket closed count equal 2

  Scenario: Failing fast configuration is turned off for complex scenario
    Given I active messaging for namespaces with fail fast false
      | Test\Ecotone\EventSourcing\Fixture\Ticket                      |
      | Test\Ecotone\EventSourcing\Fixture\TicketProjectionState                      |
    And I register "alert" ticket 123 with assignation to "Johny"
    And I register "alert" ticket 1234 with assignation to "Johny"
    And I close ticket with id 1234
    And I close ticket with id 123
    When I reset the projection "ticketCounter"
    Then I should see ticket count equal 2 and ticket closed count equal 2
    When I register "alert" ticket 12345 with assignation to "Johny"
    Then I should see ticket count equal 3 and ticket closed count equal 2