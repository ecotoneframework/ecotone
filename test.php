<?php
require __DIR__."/vendor/autoload.php";

$time = microtime(true) * 1000;
for ($i = 0; $i < 2000; $i++) {
    $tmp = \SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall::createFromUnknownType(
        \Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithThreeArguments::class,
        "calculate"
    );
}

echo (microtime(true) * 1000) - $time . "\n";