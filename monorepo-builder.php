<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    // where are the packages located?
    $parameters->set(Option::PACKAGE_DIRECTORIES, [
        __DIR__ . '/packages'
    ]);

    $parameters->set(Option::DATA_TO_APPEND, [
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
