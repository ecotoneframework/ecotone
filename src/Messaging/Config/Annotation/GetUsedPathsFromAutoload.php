<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Config\Annotation;

/**
 * Class GetUsedPathsFromAutoload
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetUsedPathsFromAutoload
{
    /**
     * @param array $requiredNamespaces
     * @param array $autoload
     *
     * @param bool $autoloadPsr4
     * @return array
     */
    public function getFor(array $requiredNamespaces, array $autoload, bool $autoloadPsr4)
    {
        $paths = [];
        foreach ($requiredNamespaces as $requiredNamespace) {
            $requiredNamespace = trim($requiredNamespace, "\\");
            $requiredNamespaceSplit = explode("\\", $requiredNamespace);

            foreach ($autoload as $namespace => $namespacePaths) {
                $namespace = trim($namespace, "\\");
                foreach ($namespacePaths as $namespacePath) {
                    $suffixPath = $namespacePath . "\\";
                    $namespaceSplit = explode("\\", $namespace);
                    $isPartOfRequiredNamespace = true;
                    foreach ($requiredNamespaceSplit as $index => $requiredNamespacePart) {
                        if (!isset($namespaceSplit[$index])) {
                            $suffixPath .= $requiredNamespacePart . "\\";
                            continue;
                        }

                        if ($requiredNamespacePart !== $namespaceSplit[$index]) {
                            $isPartOfRequiredNamespace = false;
                        }
                    }
                    if (!$isPartOfRequiredNamespace) {
                        continue;
                    }

                    if (!$autoloadPsr4) {
                        $suffixPath = $namespacePath . "\\" . $namespace;
                    }

                    $paths[] = str_replace("\\", "/", rtrim($suffixPath, "\\"));
                }
            }
        }

        return $paths;
    }
}