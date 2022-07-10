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
use InvalidArgumentException;
use function json_decode;

/**
 * Class FileSystemAnnotationRegistrationService
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemAnnotationFinder implements AnnotationFinder
{
    const         FRAMEWORK_NAMESPACE   = 'Ecotone';
    private const FILE_EXTENSION        = 'php';
    const         CLASS_NAMESPACE_REGEX = "#namespace[\s]*([^\n\s\(\)\[\]\{\}\$]*);#";

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

    public function __construct(AnnotationResolver $annotationResolver, AutoloadNamespaceParser $autoloadNamespaceParser, string $rootProjectDir, array $namespaces, string $environmentName, string $catalogToLoad)
    {
        $this->annotationResolver = $annotationResolver;
        $this->init($rootProjectDir, array_unique($namespaces), $catalogToLoad, $autoloadNamespaceParser);

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
                $classAnnotations  = array_values(
                    array_filter(
                        $methodAnnotations = array_map(
                            function (object $annotation) {
                                if ($annotation instanceof Environment) {
                                    return $annotation;
                                }

                                return null;
                            }, $this->getCachedAnnotationsForClass($className)
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

                                return null;
                            }, $this->getCachedMethodAnnotations($className, $method)
                        )
                    )
                );

                if ($methodAnnotations) {
                    if (!in_array($environmentName, $methodAnnotations[0]->getNames())) {
                        $this->bannedEnvironmentClassMethods[$className][$method] = true;
                    }
                } else if ($classAnnotations) {
                    if (!in_array($environmentName, $classAnnotations[0]->getNames())) {
                        $this->bannedEnvironmentClassMethods[$className][$method] = true;
                    }
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function findAnnotatedClasses(string $annotationClassName): array
    {
        if ($annotationClassName == "*") {
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
    public function findAnnotatedMethods(string $methodAnnotationClassName): array
    {
        $registrations = [];
        foreach ($this->findAnnotatedClasses("*") as $className) {
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

    public function getAttributeForClass(string $className, string $attributeClassName): object
    {
        $attributes = $this->getAnnotationsForClass($className);
        foreach ($attributes as $attributeToVerify) {
            if (TypeDescriptor::createFromVariable($attributeToVerify)->isCompatibleWith(TypeDescriptor::create($attributeClassName))) {
                return $attributeToVerify;
            }
        }

        throw \Ecotone\Messaging\Support\InvalidArgumentException::create("Can't find attribute {$attributeClassName} for {$className}");
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
            $registrations, function (AnnotatedDefinition $annotationRegistration, AnnotatedDefinition $annotationRegistrationToCheck) {
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
    public function getAnnotationsForMethod(string $className, string $methodName): array
    {
        return $this->getCachedMethodAnnotations($className, $methodName);
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): array
    {
        return $this->annotationResolver->getAnnotationsForProperty($className, $propertyName);
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

    private function init(string $rootProjectDir, array $namespacesToUse, string $catalogToLoad, AutoloadNamespaceParser $autoloadNamespaceParser)
    {
        $classes      = [];
        $composerPath = rtrim(realpath($rootProjectDir), "/") . "/composer.json";
        if ($catalogToLoad && !file_exists($composerPath)) {
            throw new InvalidArgumentException("Ecotone requires psr-4 or psr-0 compatible autoload. Can't load src, composer.json not found in {$composerPath}");
        }
        $catalogRelatedNamespaces = [];
        if ($catalogToLoad) {
            $composerJsonDecoded = json_decode(file_get_contents($composerPath), true);

            if (isset($composerJsonDecoded['autoload'])) {
                $catalogRelatedNamespaces = array_merge($catalogRelatedNamespaces, $autoloadNamespaceParser->getNamespacesForGivenCatalog($composerJsonDecoded['autoload'], $catalogToLoad));
            }
            if (isset($composerJsonDecoded['autoload-dev'])) {
                $catalogRelatedNamespaces = array_merge($catalogRelatedNamespaces, $autoloadNamespaceParser->getNamespacesForGivenCatalog($composerJsonDecoded['autoload-dev'], $catalogToLoad));
            }
        }

        $namespacesToUse = array_map(fn (string $namespace) => trim($namespace, "\t\n\r\\"), $namespacesToUse);
        $catalogRelatedNamespaces = array_map(fn (string $namespace) => trim($namespace, "\t\n\r\\"), $catalogRelatedNamespaces);

        if (!$catalogRelatedNamespaces && $catalogToLoad && ($namespacesToUse == ["Ecotone"] || $namespacesToUse == [])) {
            throw ConfigurationException::create("Ecotone cannot resolve namespaces in {$rootProjectDir}/$catalogToLoad. Please provide namespaces manually via configuration. If you do not know how to do it, read Modules section related to your framework at https://docs.ecotone.tech");
        }

        $paths = $this->getPathsToSearchIn($autoloadNamespaceParser, $rootProjectDir, $namespacesToUse);
        if ($catalogToLoad) {
            $paths[] = $rootProjectDir . DIRECTORY_SEPARATOR . $catalogToLoad;
        }

        $namespacesToUse = array_merge($namespacesToUse, $catalogRelatedNamespaces);
        foreach ($paths as $path) {
            $files = $this->getDirContents($path);

            foreach ($files as $file) {
                if (preg_match_all(self::CLASS_NAMESPACE_REGEX, file_get_contents($file), $results)) {
                    $namespace = isset($results[1][0]) ? trim($results[1][0]) : "";
                    $namespace = trim($namespace, "\t\n\r\\");

                    if ($this->isInAvailableNamespaces($namespacesToUse, $namespace)) {
                        $classes[] = $namespace . '\\' . basename($file, ".php");
                    }
                }
            }
        }

        $this->registeredClasses = array_unique($classes);
    }

    private function getPathsToSearchIn(AutoloadNamespaceParser $autoloadNamespaceParser, string $rootProjectDir, array $namespaces): array
    {
        $paths = [];

        $autoloadPsr4 = require($rootProjectDir . '/vendor/composer/autoload_psr4.php');
        $autoloadPsr0 = require($rootProjectDir . '/vendor/composer/autoload_namespaces.php');
        $paths        = array_merge($paths, $autoloadNamespaceParser->getFor($namespaces, $autoloadPsr4, true));
        $paths        = array_merge($paths, $autoloadNamespaceParser->getFor($namespaces, $autoloadPsr0, false));

        return array_unique($paths);
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
                $namespaceSuffix = str_replace($namespaceToUse, "", $namespace);

                if ($namespaceSuffix === "" || $namespaceSuffix[0] === "\\") {
                    return true;
                }
            }
        }

        return false;
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

        $annotations                                             = $this->annotationResolver->getAnnotationsForMethod($className, $methodName);
        $this->cachedMethodAnnotations[$className . $methodName] = $annotations;

        return $annotations;
    }

    private function isMethodBannedFromCurrentEnvironment(string $className, string $methodName)
    {
        return isset($this->bannedEnvironmentClassMethods[$className][$methodName]);
    }

    private function getDirContents(string $dir, array &$results = []) : array
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
            } else if ($value != "." && $value != "..") {
                $this->getDirContents($fullPath, $results);
            }
        }

        return $results;
    }
}