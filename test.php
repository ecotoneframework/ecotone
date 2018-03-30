<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

$tmp = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();

$mapping = "orders[*].buyerId";
$placeUnderKey = "";

$replyExpression = "payload";
$replyMappingName = "personId";

$inputMessage =
    [
        [
            "buyerId" => "123"
        ],
        [
            "buyerId" => "129"
        ]
    ];
$inputMessage =
    [
        "123", "129"
    ];


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