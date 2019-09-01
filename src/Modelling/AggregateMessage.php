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
    const AGGREGATE_OBJECT = "domain.aggregate";
    const CLASS_NAME = "domain.aggregate.class_name";
    const METHOD_NAME = "domain.aggregate.method";
    const IS_FACTORY_METHOD = "domain.aggregate.is_factory_method";
    const AGGREGATE_ID = "domain.aggregate.id";
    const TARGET_VERSION = "domain.aggregate.target_version";
    const CALLING_MESSAGE = "domain.aggregate.calling_message";

    const BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE = MethodInterceptor::DEFAULT_PRECEDENCE + 10;
}