<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;

/**
 * Interface CQRS
 * @package SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateMessage
{
    const AGGREGATE_OBJECT = "domain.aggregate";
    const CLASS_NAME = "domain.aggregate.class_name";
    const METHOD_NAME = "domain.aggregate.method";
    const IS_FACTORY_METHOD = "domain.aggregate.is_factory_method";
    const AGGREGATE_ID = "domain.aggregate.id";
    const EXPECTED_VERSION = "domain.aggregate.expected_version";
    const CALLING_MESSAGE = "domain.aggregate.calling_message";

    const BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE = MethodInterceptor::DEFAULT_PRECEDENCE + 10;
}