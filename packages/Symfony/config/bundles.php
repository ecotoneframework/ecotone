<?php

use Ecotone\SymfonyBundle\EcotoneSymfonyBundle;
use Fixture\TestBundle;

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    TestBundle::class => ['all' => true],
    EcotoneSymfonyBundle::class => ['all' => true],
    \FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle::class => ['all' => true],
];
