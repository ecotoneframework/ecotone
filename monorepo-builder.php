<?php

declare(strict_types=1);

use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\Config\MBConfig;

return static function (MBConfig $containerConfigurator): void {
    $containerConfigurator->packageDirectories([__DIR__ . '/packages']);
    $containerConfigurator->dataToAppend([
        ComposerJsonSection::AUTOLOAD_DEV => [
            'psr-4' => [
                "Tests\\Ecotone\\" => "tests",
                "IncorrectAttribute\\" => [
                    "tests\\AnnotationFinder\\Fixture\\Usage\\Attribute\\TestingNamespace\\IncorrectAttribute\\TestingNamespace"
                ],
            ],
        ],

        ComposerJsonSection::REQUIRE_DEV => [
            "behat/behat" => "^3.10",
            "friendsofphp/php-cs-fixer" => "^3.9",
            "php-coveralls/php-coveralls" => "^2.5",
            "phpstan/phpstan" => "^1.8",
            "phpunit/phpunit" => "^9.5",
            "symfony/expression-language" => "^6.0",
            "symplify/monorepo-builder" => "^11.0"
        ],
    ]);
};