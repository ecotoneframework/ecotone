<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;

/**
 * Interface CQRS
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateMessage
{
    const AGGREGATE_OBJECT = "ecotone.modelling.aggregate";
    const CLASS_NAME = "ecotone.modelling.aggregate.class_name";
    const METHOD_NAME = "ecotone.modelling.aggregate.method";
    const IS_FACTORY_METHOD = "ecotone.modelling.aggregate.is_factory_method";
    const AGGREGATE_ID = "ecotone.modelling.aggregate.id";
    const TARGET_VERSION = "ecotone.modelling.aggregate.target_version";
    const CALLING_MESSAGE = "ecotone.modelling.aggregate.calling_message";
    const IS_EVENT_SOURCED = "ecotone.modelling.aggregate.is_event_sourced";

    const BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE = MethodInterceptor::DEFAULT_PRECEDENCE + 10;
}