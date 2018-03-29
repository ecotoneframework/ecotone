<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$tmp = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();

$mapping = "orders[*].buyerId";
$placeUnderKey = "buyer";
$inputMappingName = "buyerId";
$replyMappingName = "personId";

$result = [
    [
        'personId' => "123",
        "name" => "jonhy",
        "surname" => "macarony"
    ],
    [
        'personId' => "129",
        "name" => "sylvia",
        "surname" => "rumulo"
    ]
];