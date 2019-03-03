<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation;

use Doctrine\Common\Annotations\Reader;
use SimplyCodedSoftware\Messaging\Annotation\Environment;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\AnnotationParser;

/**
 * Class FileSystemAnnotationRegistrationService
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FileSystemAnnotationRegistrationService implements AnnotationRegistrationService, AnnotationParser
{
    const INTEGRATION_MESSAGING_NAMESPACE = 'IntegrationMessaging';
    const SIMPLY_CODED_SOFTWARE_NAMESPACE = 'SimplyCodedSoftware';
    private const FILE_EXTENSION = '.php';

    /**
     * @var Reader
     */
    private $annotationReader;
    /**
     * @var string[]
     */
    private $registeredClasses;

    /**
     * @var array|AnnotationRegistration[]
     */
    private $initializedRegistrations = [];
    /**
     * @var array
     */
    private $bannedEnvironmentClassMethods = [];

    /**
     * FileSystemAnnotationRegistrationService constructor.
     * @param Reader $annotationReader
     * @param string $rootProjectDir
     * @param array $namespaces to autoload, if loadSrc is set then no need to provide src namespaces
     * @param string $environmentName
     * @param bool $loadSrc
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct(Reader $annotationReader, string $rootProjectDir, array $namespaces, string $environmentName, bool $loadSrc)
    {
        $this->annotationReader = $annotationReader;
        $this->init($rootProjectDir, array_unique($namespaces), $loadSrc);

        $classNamesWithEnvironment = $this->getAllClassesWithAnnotation(Environment::class);
        foreach ($classNamesWithEnvironment as $classNameWithEnvironment) {
            /** @var Environment $environment */
            $environment = $this->getAnnotationForClass($classNameWithEnvironment, Environment::class);

            if (!in_array($environmentName, $environment->names)) {
                $key = array_search($classNameWithEnvironment, $this->registeredClasses);
                if ($key) {
                    unset($this->registeredClasses[$key]);
                    $this->registeredClasses = array_values($this->registeredClasses);
                }
            }
        }


        foreach ($this->registeredClasses as $className) {
            foreach (get_class_methods($className) as $method) {
                $methodAnnotations = $this->getMethodAnnotations($className, $method, Environment::class);
                foreach ($methodAnnotations as $methodAnnotation) {
                    if ($methodAnnotation instanceof Environment) {
                        if (!in_array($environmentName, $methodAnnotation->names)) {
                            $this->bannedEnvironmentClassMethods[$className][$method] = true;
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function findRegistrationsFor(string $classAnnotationName, string $methodAnnotationClassName): array
    {
        $registrations = [];
        foreach ($this->getAllClassesWithAnnotation($classAnnotationName) as $className) {
            foreach (get_class_methods($className) as $method) {
                if ($this->isMethodBannedFromCurrentEnvironment($className, $method)) {
                    continue;
                }

                $methodAnnotations = $this->getMethodAnnotations($className, $method, $methodAnnotationClassName);
                foreach ($methodAnnotations as $methodAnnotation) {
                    $annotationReference = $className . get_class($methodAnnotation);
                    if (array_key_exists($annotationReference, $this->initializedRegistrations)) {
                        $registrations[] = $this->initializedRegistrations[$annotationReference];

                        continue;
                    }

                    if (get_class($methodAnnotation) === $methodAnnotationClassName || $methodAnnotation instanceof $methodAnnotationClassName) {
                        $annotationRegistration = AnnotationRegistration::create(
                            $this->getAnnotationForClass($className, $classAnnotationName),
                            $methodAnnotation,
                            $className,
                            $method
                        );

                        $registrations[] = $annotationRegistration;
                        $this->initializedRegistrations[$annotationReference] = $annotationRegistration;
                    }
                }
            }
        }

        return $registrations;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForMethod(string $className, string $methodName): iterable
    {
        $reflectionMethod = new \ReflectionMethod($className, $methodName);

        return $this->annotationReader->getMethodAnnotations($reflectionMethod);
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForClass(string $className): iterable
    {
        $reflectionClass = new \ReflectionClass($className);

        return $this->annotationReader->getClassAnnotations($reflectionClass);
    }

    /**
     * @inheritDoc
     */
    public function getAllClassesWithAnnotation(string $annotationClassName): array
    {
        if ($annotationClassName == "*") {
            return $this->registeredClasses;
        }

        $classesWithAnnotations = [];
        foreach ($this->registeredClasses as $class) {
            $classReflection = new \ReflectionClass($class);

            $classAnnotation = $this->annotationReader->getClassAnnotation($classReflection, $annotationClassName);

            if ($classAnnotation) {
                $classesWithAnnotations[] = $class;
            }
        }

        return $classesWithAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationForClass(string $className, string $annotationClassName)
    {
        return $this->annotationReader->getClassAnnotation(new \ReflectionClass($className), $annotationClassName);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return bool
     */
    private function isMethodBannedFromCurrentEnvironment(string $className, string $methodName)
    {
        return isset($this->bannedEnvironmentClassMethods[$className][$methodName]);
    }

    /**
     * @param string $rootProjectDir
     * @param array $namespaces
     * @param bool $loadSrc
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function init(string $rootProjectDir, array $namespaces, bool $loadSrc)
    {
        $classes = [];
        $paths = $this->getPathsToSearchIn($rootProjectDir, $namespaces, $loadSrc);

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                throw ConfigurationException::create("There is no path: {$path}");
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                $fileName = $file->getBasename(self::FILE_EXTENSION);

                if ($this->isDirectory($fileName, $file)) {
                    continue;
                }
                if ($this->isPHPFile($file)) {
                    continue;
                }

                $file = $file->openFile();
                while (!$file->eof()) {
                    $line = $file->current();
                    if ($line == false) {
                        break;
                    }

                    if (preg_match_all("#namespace[\s]*([^\n\s\(\)\[\]\{\}\$]*);#", $line, $results)) {
                        $namespace = isset($results[1][0]) ? trim($results[1][0]) : "";
                        $namespace = trim($namespace);

//                        Add all in resolved paths
                        if ($this->isInAvailableNamespaces($namespaces, $namespace)) {
                            $classes[] = trim($namespace) . '\\' . $fileName;
                            break;
                        }
                    }

                    $file->next();
                }
            }
        }

        $this->registeredClasses = array_unique($classes);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param string $annotationName
     * @return object[]
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getMethodAnnotations(string $className, string $methodName, string $annotationName) : array
    {
        try {
            $reflectionMethod = new \ReflectionMethod($className, $methodName);

            return $this->annotationReader->getMethodAnnotations($reflectionMethod);
        }catch (\ReflectionException $e) {
            throw ConfigurationException::create("Class {$className} with method {$methodName} and annotation {$annotationName} does not exists or got annotation configured wrong: " . $e->getMessage());
        }
    }

    /**
     * @param $fileName
     * @param $file
     * @return bool
     */
    private function isDirectory($fileName, \SplFileInfo $file): bool
    {
        return $fileName == $file->getBasename();
    }

    /**
     * @param $file
     * @return bool
     */
    private function isPHPFile(\SplFileInfo $file): bool
    {
        return $file->getFileInfo()->getExtension() == self::FILE_EXTENSION;
    }

    /**
     * @param string $rootProjectDir
     * @param array $namespaces
     * @param bool $loadSrc
     * @return array
     */
    private function getPathsToSearchIn(string $rootProjectDir, array $namespaces, bool $loadSrc): array
    {
        $paths = [];

        $namespaceToRegex = "";
        $isFirst = true;
        foreach ($namespaces as $namespace) {
            $namespaceSplit = explode("\\", $namespace);
            $rootNamespace = $namespaceSplit[0];

            $namespaceToRegex .= $isFirst ? ("^" . $rootNamespace) : ("|^" . $rootNamespace);
            $isFirst = false;
        }

        $autoloadPsr4 = require($rootProjectDir . '/vendor/composer/autoload_psr4.php');
        $autoloadPsr0 = require($rootProjectDir . '/vendor/composer/autoload_namespaces.php');
        $paths = $this->mergeWith($namespaceToRegex, $autoloadPsr4, $paths, $rootProjectDir, $loadSrc);
        $paths = $this->mergeWith($namespaceToRegex, $autoloadPsr0, $paths, $rootProjectDir, $loadSrc);

        return array_unique($paths);
    }

    /**
     * @param array $namespaces
     * @param $namespace
     * @return bool
     */
    private function isInAvailableNamespaces(array $namespaces, $namespace): bool
    {
        foreach ($namespaces as $namespaceToUse) {
            $namespaceToUse = trim($namespaceToUse);
            if (strpos($namespace, trim($namespaceToUse)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $namespaceToRegex
     * @param array $autoload
     * @param array $paths
     *
     * @param string $rootProjectDir
     * @param bool $loadSrc
     * @return array
     */
    private function mergeWith(string $namespaceToRegex, array $autoload, array $paths, string $rootProjectDir, bool $loadSrc)
    {
        $regex = "#{$namespaceToRegex}#";
        $resolvedPathToSrc = realpath($rootProjectDir) . '/src';
        foreach ($autoload as $namespace => $namespacePath) {
            if ($loadSrc && (in_array($resolvedPathToSrc, $namespacePath)) || preg_match($regex, $namespace)) {
                foreach ($namespacePath as $pathForNamespace) {
                    $paths[] = $pathForNamespace;
                }
            }
        }

        return $paths;
    }
}