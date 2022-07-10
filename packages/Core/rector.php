<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector::class);
//    $services->set(Rector\TypeDeclaration\Rector\Property\TypedPropertyRector::class);
//    $services->set(Rector\Php74\Rector\Property\TypedPropertyRector::class);

    // get parameters
    $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        SetList::DEAD_CODE,
    ]);
    $parameters->set('php_version_features', '7.4');

    $parameters->set(Option::EXCLUDE_RECTORS, [
        \Rector\DeadCode\Rector\FunctionLike\RemoveOverriddenValuesRector::class,
        Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector::class,
        Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector::class,
        Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class,
        Rector\DeadCode\Rector\Property\RemoveSetterOnlyPropertyAndMethodCallRector::class,
        Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class,
        Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class,
        Rector\DeadCode\Rector\ClassMethod\RemoveDeadConstructorRector::class
    ]);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
};
