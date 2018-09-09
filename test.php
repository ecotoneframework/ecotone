<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$message = \SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder::withPayload(new \stdClass())
            ->setHeader("jhony", \SimplyCodedSoftware\IntegrationMessaging\Handler\Filter\MessageFilterBuilder::createWithReferenceName("some", "bla"))
            ->build();

$tmp = \json_encode($message->getPayload());
var_dump(\json_encode($message->getPayload()));
var_dump(\json_encode($message->getHeaders()->headers()));