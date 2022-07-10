<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Modelling\AggregateId;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class AggregateIdTest extends TestCase
{
    public function test_resolving_scalar_type()
    {
        $this->assertEquals(123, AggregateId::resolve(\stdClass::class, 123));
        $this->assertEquals("someId", AggregateId::resolve(\stdClass::class,"someId"));
    }

    public function test_resolving_object_with_to_string_method()
    {
        $this->assertEquals('5495ec27-c286-48fe-aed4-2548c1113c37', Uuid::fromString('5495ec27-c286-48fe-aed4-2548c1113c37'));
    }

    public function test_throwing_exception_if_aggregate_id_is_class_without_to_string_method()
    {
        $this->expectException(NoCorrectIdentifierDefinedException::class);

        AggregateId::resolve(\stdClass::class,new \stdClass());
    }

    public function test_throwing_exception_if_aggregate_id_is_array()
    {
        $this->expectException(NoCorrectIdentifierDefinedException::class);

        AggregateId::resolve(\stdClass::class, ["johny"]);
    }
}