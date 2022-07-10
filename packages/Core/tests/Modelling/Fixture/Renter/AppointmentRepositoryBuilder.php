<?php


namespace Test\Ecotone\Modelling\Fixture\Renter;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\RepositoryBuilder;
use Ecotone\Modelling\StandardRepository;

class AppointmentRepositoryBuilder implements RepositoryBuilder
{
    /**
     * @var Appointment[]
     */
    private $appointments = [];

    private function __construct(array $appointments)
    {
        /** @var Appointment $appointment */
        foreach ($appointments as $appointment) {
            $this->appointments[$appointment->getAppointmentId()] = $appointment;
        }
    }

    public static function createEmpty() : self
    {
        return new self([]);
    }

    public static function createWith(array $appointments) : self
    {
        return new self($appointments);
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Appointment::class;
    }

    public function isEventSourced(): bool
    {
        return false;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): EventSourcedRepository|StandardRepository
    {
        return AppointmentStandardRepository::createWith($this->appointments);
    }
}