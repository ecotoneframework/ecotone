<?php

namespace SimplyCodedSoftware\DomainModel;

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

    const BEFORE_CONVERTER_INTERCEPTOR = -1;
    const AGGREGATE_SEND_MESSAGE_CHANNEL = "domain.aggregate.send_message";
    const AGGREGATE_MESSAGE_CHANNEL_NAME_TO_SEND = "domain.aggregate.send_message.channel_name";
}