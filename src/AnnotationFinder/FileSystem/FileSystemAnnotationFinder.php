<?php

declare(strict_types=1);

namespace Ecotone\AnnotationFinder\FileSystem;

use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedMethod;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\Attribute\Environment;
use Ecotone\AnnotationFinder\ConfigurationException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class FileSystemAnnotationRegistrationService
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemAnnotationFinder implements AnnotationFinder
{
    private const FILE_EXTENSION = 'php';
    public const         CLASS_NAMESPACE_REGEX = "#namespace[\s]*([^\n\s\(\)\[\]\{\}\$]*);#";

    /**
     * @var string[]
     */
    private array $registeredClasses;
    /**
     * @var array
     */
    private array $bannedEnvironmentClassMethods = [];
    /**
     * @var string[]
     */
    private array $cachedMethodAnnotations = [];

    /**
     * @var object[][]
     */
    private array $cachedClassAnnotations = [];
    private AnnotationResolver $annotationResolver;

    public function __construct(
        AnnotationResolver      $annotationResolver,
        AutoloadNamespaceParser $autoloadNamespaceParser,
        string                  $rootProjectDir,
        array                   $namespaces,
        string                  $environmentName,
        string                  $catalogToLoad,
        array                   $systemClassesToRegister = [],
        array                   $userClassesToRegister = [],
        bool                    $isRunningForTesting = false
    )
    {
        $this->annotationResolver = $annotationResolver;
        $this->init($rootProjectDir, array_unique($namespaces), $catalogToLoad, $autoloadNamespaceParser, $systemClassesToRegister, $userClassesToRegister, $isRunningForTesting);

        $classNamesWithEnvironment = $this->findAnnotatedClasses(Environment::class);
        foreach ($classNamesWithEnvironment as $classNameWithEnvironment) {
            /** @var Environment $environment */
            $environment = $this->getAnnotationForClass($classNameWithEnvironment, Environment::class);

            if (!in_array($environmentName, $environment->getNames())) {
                $key = array_search($classNameWithEnvironment, $this->registeredClasses);
                if ($key !== false) {
                    unset($this->registeredClasses[$key]);
                    $this->registeredClasses = array_values($this->registeredClasses);
                }
            }
        }

        foreach ($this->registeredClasses as $className) {
            foreach (get_class_methods($className) as $method) {
                $classAnnotations = array_values(
                    array_filter(
                        array_map(
                            function (object $annotation) {
                                if ($annotation instanceof Environment) {
                                    return $annotation;
                                }
                            },
                            $this->getCachedAnnotationsForClass($className)
                        )
                    )
                );
                $methodAnnotations = array_values(
                    array_filter(
                        array_map(
                            function (object $annotation) {
                                if ($annotation instanceof Environment) {
                                    return $annotation;
                                }
                            },
                            $this->getCachedMethodAnnotations($className, $method)
                        )
                    )
                );

                if ($methodAnnotations) {
                    if (!in_array($environmentName, $methodAnnotations[0]->getNames())) {
                        $this->bannedEnvironmentClassMethods[$className][$method] = true;
                    }
                } elseif ($classAnnotations) {
                    if (!in_array($environmentName, $classAnnotations[0]->getNames())) {
                        $this->bannedEnvironmentClassMethods[$className][$method] = true;
                    }
                }
            }
        }
    }

    private function init(string $rootProjectDir, array $namespacesToUse, string $catalogToLoad, AutoloadNamespaceParser $autoloadNamespaceParser, array $systemClassesToRegister, array $userClassesToRegister, bool $isRunningForTesting)
    {
        if (!$catalogToLoad && $namespacesToUse == [] && $userClassesToRegister == [] && !$isRunningForTesting) {
            throw ConfigurationException::create('Loading catalog was turned off and no namespaces were provided. Please provide namespaces manually via configuration or turn on catalog loading. Read related Module section at https://docs.ecotone.tech');
        }

        $registeredClasses = array_merge($systemClassesToRegister, $userClassesToRegister);
        if ($catalogToLoad) {
            $registeredClasses = array_merge(
                $registeredClasses,
                $this->getClassesIn([$rootProjectDir . DIRECTORY_SEPARATOR . $catalogToLoad], null)
            );
        }

        $registeredClasses = array_merge(
            $registeredClasses,
            $this->getRegisteredClassesForNamespaces($rootProjectDir, $autoloadNamespaceParser, $namespacesToUse)
        );

        $this->registeredClasses = array_unique($registeredClasses);
    }

    private function getPathsToSearchIn(AutoloadNamespaceParser $autoloadNamespaceParser, string $rootProjectDir, array $namespaces): array
    {
        $paths = [];

        $autoloadPsr4 = require($rootProjectDir . '/vendor/composer/autoload_psr4.php');
        $autoloadPsr0 = require($rootProjectDir . '/vendor/composer/autoload_namespaces.php');
        $paths = array_merge($paths, $autoloadNamespaceParser->getFor($namespaces, $autoloadPsr4, true));
        $paths = array_merge($paths, $autoloadNamespaceParser->getFor($namespaces, $autoloadPsr0, false));

        return array_unique($paths);
    }

    private function getDirContents(string $dir, array &$results = []): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $fullPath = realpath($dir . DIRECTORY_SEPARATOR . $value);
            Assert::isTrue($fullPath !== false, "Can't parse contents of " . $dir . DIRECTORY_SEPARATOR . $value);

            if (!is_dir($fullPath)) {
                if (pathinfo($fullPath, PATHINFO_EXTENSION) === self::FILE_EXTENSION) {
                    $results[] = $fullPath;
                }
            } elseif ($value != '.' && $value != '..') {
                $this->getDirContents($fullPath, $results);
            }
        }

        return $results;
    }

    /**
     * @param array $namespaces
     * @param       $namespace
     *
     * @return bool
     */
    private function isInAvailableNamespaces(array $namespaces, $namespace): bool
    {
        foreach ($namespaces as $namespaceToUse) {
            if (strpos($namespace, $namespaceToUse) === 0) {
                $namespaceSuffix = str_replace($namespaceToUse, '', $namespace);

                if ($namespaceSuffix === '' || $namespaceSuffix[0] === '\\') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function findAnnotatedClasses(string $annotationClassName): array
    {
        if ($annotationClassName == '*') {
            return $this->registeredClasses;
        }

        $classesWithAnnotations = [];
        foreach ($this->registeredClasses as $class) {
            $classAnnotation = $this->getAnnotationForClass($class, $annotationClassName);

            if ($classAnnotation) {
                $classesWithAnnotations[] = $class;
            }
        }

        return $classesWithAnnotations;
    }

    private function getAnnotationForClass(string $className, string $annotationClassNameToFind): ?object
    {
        $annotationsForClass = $this->getAnnotationsForClass($className);
        $resolvedAnnotations = [];
        foreach ($annotationsForClass as $annotationForClass) {
            if (is_a($annotationForClass, $annotationClassNameToFind)) {
                $resolvedAnnotations[] = $annotationForClass;
            }
        }

        if (count($resolvedAnnotations) > 1) {
            throw new ConfigurationException("Expects to be single {$annotationClassNameToFind} annotation for class {$className}, but found more.");
        }

        return empty($resolvedAnnotations) ? null : $resolvedAnnotations[0];
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForClass(string $className): array
    {
        return $this->getCachedAnnotationsForClass($className);
    }

    /**
     * @inheritDoc
     */
    private function getCachedAnnotationsForClass(string $className): array
    {
        if (isset($this->cachedClassAnnotations[$className])) {
            return $this->cachedClassAnnotations[$className];
        }

        $classAnnotations = $this->annotationResolver->getAnnotationsForClass($className);
        $this->cachedClassAnnotations[$className] = $classAnnotations;

        return $classAnnotations;
    }

    /**
     * @return object[]
     */
    private function getCachedMethodAnnotations(string $className, string $methodName): array
    {
        if (isset($this->cachedMethodAnnotations[$className . $methodName])) {
            return $this->cachedMethodAnnotations[$className . $methodName];
        }

        $annotations = $this->annotationResolver->getAnnotationsForMethod($className, $methodName);
        $this->cachedMethodAnnotations[$className . $methodName] = $annotations;

        return $annotations;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForMethod(string $className, string $methodName): array
    {
        return $this->getCachedMethodAnnotations($className, $methodName);
    }

    /**
     * @inheritDoc
     */
    public function findAnnotatedMethods(string $methodAnnotationClassName): array
    {
        $registrations = [];
        foreach ($this->findAnnotatedClasses('*') as $className) {
            foreach (get_class_methods($className) as $method) {
                if ($this->isMethodBannedFromCurrentEnvironment($className, $method)) {
                    continue;
                }

                $methodAnnotations = $this->getCachedMethodAnnotations($className, $method);
                foreach ($methodAnnotations as $methodAnnotation) {
                    if (get_class($methodAnnotation) === $methodAnnotationClassName || $methodAnnotation instanceof $methodAnnotationClassName) {
                        $annotationRegistration = AnnotatedMethod::create(
                            $methodAnnotation,
                            $className,
                            $method,
                            $this->getCachedAnnotationsForClass($className),
                            $methodAnnotations
                        );

                        $registrations[] = $annotationRegistration;
                    }
                }
            }
        }

        return $registrations;
    }

    private function isMethodBannedFromCurrentEnvironment(string $className, string $methodName)
    {
        return isset($this->bannedEnvironmentClassMethods[$className][$methodName]);
    }

    public function getAttributeForClass(string $className, string $attributeClassName): object
    {
        $attributes = $this->getAnnotationsForClass($className);
        foreach ($attributes as $attributeToVerify) {
            if (TypeDescriptor::createFromVariable($attributeToVerify)->isCompatibleWith(TypeDescriptor::create($attributeClassName))) {
                return $attributeToVerify;
            }
        }

        throw InvalidArgumentException::create("Can't find attribute {$attributeClassName} for {$className}");
    }

    /**
     * @inheritDoc
     */
    public function findCombined(string $classAnnotationName, string $methodAnnotationClassName): array
    {
        $registrations = [];
        foreach ($this->findAnnotatedClasses($classAnnotationName) as $className) {
            foreach (get_class_methods($className) as $method) {
                if ($this->isMethodBannedFromCurrentEnvironment($className, $method)) {
                    continue;
                }

                $methodAnnotations = $this->getCachedMethodAnnotations($className, $method);
                foreach ($methodAnnotations as $methodAnnotation) {
                    if (get_class($methodAnnotation) === $methodAnnotationClassName || $methodAnnotation instanceof $methodAnnotationClassName) {
                        $annotationRegistration = AnnotatedDefinition::create(
                            $this->getAnnotationForClass($className, $classAnnotationName),
                            $methodAnnotation,
                            $className,
                            $method,
                            $this->getCachedAnnotationsForClass($className),
                            $methodAnnotations
                        );

                        $registrations[] = $annotationRegistration;
                    }
                }
            }
        }

        usort(
            $registrations,
            function (AnnotatedDefinition $annotationRegistration, AnnotatedDefinition $annotationRegistrationToCheck) {
                if ($annotationRegistration->getClassName() == $annotationRegistrationToCheck->getClassName()) {
                    return 0;
                }

                return $annotationRegistration->getClassName() > $annotationRegistrationToCheck->getClassName() ? 1 : -1;
            }
        );

        return $registrations;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): array
    {
        return $this->annotationResolver->getAnnotationsForProperty($className, $propertyName);
    }

    /**
     * @param string[] $paths
     * @param string[]|null $namespacesToUse
     * @param string[] $classes
     */
    private function getClassesIn(array $paths, ?array $namespacesToUse): array
    {
        $classes = [];
        foreach ($paths as $path) {
            $files = $this->getDirContents($path);

            foreach ($files as $file) {
                if (preg_match_all(self::CLASS_NAMESPACE_REGEX, file_get_contents($file), $results)) {
                    $namespace = isset($results[1][0]) ? trim($results[1][0]) : '';
                    $namespace = trim($namespace, "\t\n\r\\");

                    if (is_null($namespacesToUse) || $this->isInAvailableNamespaces($namespacesToUse, $namespace)) {
                        $classes[] = $namespace . '\\' . basename($file, '.php');
                    }
                }
            }
        }
        return $classes;
    }

    private function getRegisteredClassesForNamespaces(string $rootProjectDir, AutoloadNamespaceParser $autoloadNamespaceParser, array $namespacesToUse): array
    {
        if ($namespacesToUse === []) {
            return [];
        }

        $originalRootProjectDir = $rootProjectDir;
        $rootProjectDir = realpath(rtrim($rootProjectDir, '/'));

        while ($rootProjectDir !== false && !file_exists($rootProjectDir . DIRECTORY_SEPARATOR . '/vendor/autoload.php')) {
            if ($rootProjectDir === DIRECTORY_SEPARATOR) {
                throw InvalidArgumentException::create(sprintf("Can't find autoload file in given path `%s/vendor/autoload.php` and any preceding ones.", $originalRootProjectDir));
            }

            $rootProjectDir = realpath($rootProjectDir . DIRECTORY_SEPARATOR . '..');
        }

        $namespacesToUse = array_map(fn(string $namespace) => trim($namespace, "\t\n\r\\"), $namespacesToUse);

        $paths = $this->getPathsToSearchIn($autoloadNamespaceParser, $rootProjectDir, $namespacesToUse);

        return $this->getClassesIn($paths, $namespacesToUse);
    }
}
