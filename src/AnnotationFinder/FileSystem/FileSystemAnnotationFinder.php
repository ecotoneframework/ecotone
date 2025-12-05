<?php

declare(strict_types=1);

namespace Ecotone\AnnotationFinder\FileSystem;

use Closure;
use Composer\Autoload\ClassLoader;
use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedMethod;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\Attribute\Environment;
use Ecotone\AnnotationFinder\ConfigurationException;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use Ecotone\Messaging\Attribute\IsAbstract;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use ReflectionClass;

/**
 * Class FileSystemAnnotationRegistrationService
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
    private IsAbstract $isAbstractAnnotation;

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
    ) {
        $this->annotationResolver = $annotationResolver;
        $this->isAbstractAnnotation = new IsAbstract();
        $this->init($rootProjectDir, array_unique($namespaces), $catalogToLoad, $autoloadNamespaceParser, $systemClassesToRegister, $userClassesToRegister, $isRunningForTesting);

        $classNamesWithEnvironment = $this->findAnnotatedClasses(Environment::class);
        foreach ($classNamesWithEnvironment as $classNameWithEnvironment) {
            /** @var Environment $environment */
            $environment = $this->getAnnotationForClass($classNameWithEnvironment, Environment::class);

            if (! in_array($environmentName, $environment->getNames())) {
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
                    if (! in_array($environmentName, $methodAnnotations[0]->getNames())) {
                        $this->bannedEnvironmentClassMethods[$className][$method] = true;
                    }
                } elseif ($classAnnotations) {
                    if (! in_array($environmentName, $classAnnotations[0]->getNames())) {
                        $this->bannedEnvironmentClassMethods[$className][$method] = true;
                    }
                }
            }
        }
    }

    public function registeredClasses(): array
    {
        return $this->registeredClasses;
    }

    private function init(string $rootProjectDir, array $namespacesToUse, string $catalogToLoad, AutoloadNamespaceParser $autoloadNamespaceParser, array $systemClassesToRegister, array $userClassesToRegister, bool $isRunningForTesting)
    {
        if (! $catalogToLoad && $namespacesToUse == [] && $userClassesToRegister == [] && ! $isRunningForTesting) {
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
            self::getRegisteredClassesForNamespaces($rootProjectDir, $autoloadNamespaceParser, $namespacesToUse)
        );

        $this->registeredClasses = array_unique($registeredClasses);
    }

    private static function getDirContents(string $dir, array &$results = []): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $fullPath = realpath($dir . DIRECTORY_SEPARATOR . $value);
            Assert::isTrue($fullPath !== false, "Can't parse contents of " . $dir . DIRECTORY_SEPARATOR . $value);

            if (! is_dir($fullPath)) {
                if (pathinfo($fullPath, PATHINFO_EXTENSION) === self::FILE_EXTENSION) {
                    $results[] = $fullPath;
                }
            } elseif ($value != '.' && $value != '..') {
                self::getDirContents($fullPath, $results);
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
    private static function isInAvailableNamespaces(array $namespaces, $namespace): bool
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

    private static function throwNotFound(string $originalRootProjectDir)
    {
        throw RootCatalogNotFound::create(sprintf("Can't find autoload file in given path `%s` and any preceding ones.", rtrim($originalRootProjectDir, '/')));
    }

    /**
     * @inheritDoc
     */
    public function findAnnotatedMethods(string $methodAnnotationClassName): array
    {
        $registrations = [];
        foreach ($this->findAnnotatedClasses('*') as $className) {
            $reflectionClass = new ReflectionClass($className);
            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $method = $reflectionMethod->getName();
                if ($this->isMethodBannedFromCurrentEnvironment($className, $method)) {
                    continue;
                }
                $classAnnotations = $this->getCachedAnnotationsForClass($className);

                if ($this->isAbstractClass($classAnnotations)) {
                    continue;
                }

                $methodAnnotations = $this->getCachedMethodAnnotations($className, $method);
                foreach ($methodAnnotations as $methodAnnotation) {
                    if (get_class($methodAnnotation) === $methodAnnotationClassName || $methodAnnotation instanceof $methodAnnotationClassName) {
                        // Validate that endpoint annotations are on public methods
                        if (
                            ($methodAnnotation instanceof IdentifiedAnnotation
                                || $methodAnnotation instanceof MessageConsumer)
                            && ! $reflectionMethod->isPublic()
                        ) {
                            $handlerType = match (true) {
                                $methodAnnotation instanceof CommandHandler => 'Command handler',
                                $methodAnnotation instanceof EventHandler => 'Event handler',
                                $methodAnnotation instanceof QueryHandler => 'Query handler',
                                $methodAnnotation instanceof MessageConsumer => 'Message consumer',
                                default => 'Handler',
                            };
                            throw ConfigurationException::create(sprintf('%s attribute on %s::%s should be placed on public method, to be available for execution.', $handlerType, $className, $method));
                        }

                        if (! $reflectionMethod->isPublic()) {
                            continue;
                        }

                        $annotationRegistration = AnnotatedMethod::create(
                            $methodAnnotation,
                            $className,
                            $method,
                            $classAnnotations,
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
        return $this->findAttributeForClass($className, $attributeClassName) ?? throw InvalidArgumentException::create("Can't find attribute {$attributeClassName} for {$className}");
    }

    public function findAttributeForClass(string $className, string $attributeClassName): ?object
    {
        $attributes = $this->getAnnotationsForClass($className);
        foreach ($attributes as $attributeToVerify) {
            if ($attributeToVerify instanceof $attributeClassName) {
                return $attributeToVerify;
            }
        }

        return null;
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
    private static function getClassesIn(array $paths, ?array $namespacesToUse): array
    {
        $classes = [];
        foreach ($paths as $path) {
            $files = self::getDirContents($path);

            foreach ($files as $file) {
                if (preg_match_all(self::CLASS_NAMESPACE_REGEX, file_get_contents($file), $results)) {
                    $namespace = isset($results[1][0]) ? trim($results[1][0]) : '';
                    $namespace = trim($namespace, "\t\n\r\\");

                    if (is_null($namespacesToUse) || self::isInAvailableNamespaces($namespacesToUse, $namespace)) {
                        $classes[] = $namespace . '\\' . basename($file, '.php');
                    }
                }
            }
        }
        return $classes;
    }

    private static function getRegisteredClassesForNamespaces(
        string $rootProjectDir,
        AutoloadNamespaceParser $autoloadNamespaceParser,
        array $namespacesToUse
    ): array {
        if ($namespacesToUse === []) {
            return [];
        }

        $rootProjectDir = self::getRealRootCatalog($rootProjectDir, $rootProjectDir);
        $namespacesToUse = array_map(fn (string $namespace) => trim($namespace, "\t\n\r\\"), $namespacesToUse);

        /** @var ClassLoader $autoloader */
        $autoloader = require($rootProjectDir . '/vendor/autoload.php');

        if ($autoloader->isClassMapAuthoritative()) {
            return array_values(
                \array_filter(
                    array_keys($autoloader->getClassMap()),
                    fn (string $className) => self::isInAvailableNamespaces($namespacesToUse, $className)
                )
            );
        } else {
            $paths = [];

            $autoloadPsr4 = require($rootProjectDir . '/vendor/composer/autoload_psr4.php');
            $autoloadPsr0 = require($rootProjectDir . '/vendor/composer/autoload_namespaces.php');
            $paths = array_merge($paths, $autoloadNamespaceParser->getFor($namespacesToUse, $autoloadPsr4, true));
            $paths = array_merge($paths, $autoloadNamespaceParser->getFor($namespacesToUse, $autoloadPsr0, false));

            $paths = array_unique($paths);

            return self::getClassesIn($paths, $namespacesToUse);
        }
    }

    private function isAbstractClass(array $classAnnotations): bool
    {
        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation == $this->isAbstractAnnotation) {
                return true;
            }
        }

        return false;
    }

    public static function getRealRootCatalog(string $rootProjectDir, string $originalRootProjectDir): string
    {
        $rootProjectDir = realpath(rtrim($rootProjectDir, '/'));
        while ($rootProjectDir !== false && ! file_exists($rootProjectDir . DIRECTORY_SEPARATOR . '/vendor/autoload.php')) {
            if ($rootProjectDir === DIRECTORY_SEPARATOR) {
                self::throwNotFound($originalRootProjectDir);
            }

            $rootProjectDir = realpath($rootProjectDir . DIRECTORY_SEPARATOR . '..');
        }

        if ($rootProjectDir === false) {
            self::throwNotFound($originalRootProjectDir);
        }

        return $rootProjectDir;
    }

    public function getCacheMessagingFileNameBasedOnConfig(
        string $pathToRootCatalog,
        ServiceConfiguration $serviceConfiguration,
        array $configurationVariables,
        bool $enableTesting
    ): string {
        // this is temporary cache based on if files have changed
        // get file contents based on class names, configuration and configuration variables
        $fileSha = '';

        foreach ($this->registeredClasses() as $class) {
            $filePath = (new ReflectionClass($class))->getFileName();
            $fileSha .= sha1_file($filePath);
        }

        if (file_exists($pathToRootCatalog . 'composer.lock')) {
            $fileSha .= sha1_file($pathToRootCatalog . 'composer.lock');
        }

        $fileSha .= sha1(serialize($serviceConfiguration));
        $fileSha .= sha1(serialize($this->skipClosures($configurationVariables)));
        $fileSha .= $enableTesting ? 'true' : 'false';

        return 'ecotone' . DIRECTORY_SEPARATOR . sha1($fileSha);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function skipClosures(iterable $configuration): array
    {
        $configurationVariables = [];
        foreach ($configuration as $key => $value) {
            if (is_iterable($value)) {
                $configurationVariables[$key] = $this->skipClosures($value);
            } elseif ($value instanceof Closure) {
                $configurationVariables[$key] = 'Closure';
            } else {
                $configurationVariables[$key] = $value;
            }
        }

        return $configurationVariables;
    }
}
